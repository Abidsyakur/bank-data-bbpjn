<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\LabelAksi;
use App\Support\PesanValidasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/** Riwayat Aktivitas (audit trail). Khusus Pimpinan, dijaga middleware role:pimpinan di routes. */
class RiwayatController extends Controller
{
    public function __invoke(Request $request): View
    {
        $filter = $request->validate([
            'pengguna' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'aksi'     => ['nullable', Rule::in(array_keys(LabelAksi::DAFTAR))],
            'dari'     => ['nullable', 'date'],
            'sampai'   => ['nullable', 'date', 'after_or_equal:dari'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $log = DB::table('log_aktivitas as l')
            ->join('users as u', 'u.id', '=', 'l.user_id')
            ->leftJoin('dokumen as d', 'd.id', '=', 'l.dokumen_id')
            ->select(
                'l.id', 'l.aksi', 'l.keterangan', 'l.waktu', 'l.dokumen_id',
                'u.nama', 'u.username',
                'd.judul as dokumen_judul', 'd.deleted_at as dokumen_dihapus'
            )
            ->when($filter['pengguna'] ?? null, fn ($q, $v) => $q->where('l.user_id', $v))
            ->when($filter['aksi'] ?? null, fn ($q, $v) => $q->where('l.aksi', $v))
            ->when($filter['dari'] ?? null, fn ($q, $v) => $q->whereDate('l.waktu', '>=', $v))
            ->when($filter['sampai'] ?? null, fn ($q, $v) => $q->whereDate('l.waktu', '<=', $v))
            ->orderByDesc('l.waktu')
            ->orderByDesc('l.id')
            ->paginate(25)
            ->withQueryString();

        return view('riwayat.index', [
            'log'      => $log,
            'filter'   => $filter,
            'pengguna' => User::orderBy('nama')->get(['id', 'nama', 'username']),
            'aksiList' => LabelAksi::DAFTAR,
        ]);
    }
}
