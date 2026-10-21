<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\TindakLanjut;
use App\Services\ActivityLogger;
use App\Services\BerkasService;
use App\Support\PesanValidasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TindakLanjutController extends Controller
{
    public function __construct(private BerkasService $berkasService)
    {
    }

    public function store(Request $request, Dokumen $dokumen): RedirectResponse
    {
        Gate::authorize('update', $dokumen);
        abort_unless($dokumen->layanan->butuh_tindak_lanjut, 404);
        abort_if($dokumen->status === Dokumen::STATUS_SELESAI, 403, 'Dokumen sudah selesai. Buka kembali untuk menambah tindak lanjut.');

        $data = $request->validate([
            'catatan' => ['required', 'string', 'max:2000'],
            'tanggal' => ['required', 'date'],
            'bukti'   => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $path = $request->hasFile('bukti')
            ? $this->berkasService->simpan($request->file('bukti'), 'tindak-lanjut')
            : null;
        $selesai = $request->boolean('selesai');

        DB::transaction(function () use ($request, $dokumen, $data, $path, $selesai) {
            TindakLanjut::create([
                'dokumen_id' => $dokumen->id,
                'user_id'    => $request->user()->id,
                'bukti_path' => $path,
                'catatan'    => $data['catatan'],
                'tanggal'    => $data['tanggal'],
            ]);

            if ($selesai) {
                $dokumen->update(['status' => Dokumen::STATUS_SELESAI]);
            }
        });

        ActivityLogger::catat('tindak_lanjut.tambah', $dokumen->judul, $dokumen->id);
        if ($selesai) {
            ActivityLogger::catat('dokumen.selesai', $dokumen->judul, $dokumen->id);
        }

        return redirect()->route('dokumen.show', $dokumen)
            ->with('status', $selesai ? 'Tindak lanjut disimpan dan dokumen ditandai selesai.' : 'Tindak lanjut berhasil disimpan.');
    }

    public function bukaKembali(Dokumen $dokumen): RedirectResponse
    {
        Gate::authorize('update', $dokumen);
        abort_unless($dokumen->layanan->butuh_tindak_lanjut, 404);

        if ($dokumen->status === Dokumen::STATUS_SELESAI) {
            $dokumen->update(['status' => Dokumen::STATUS_MENUNGGU]);
            ActivityLogger::catat('dokumen.buka_kembali', $dokumen->judul, $dokumen->id);
        }

        return redirect()->route('dokumen.show', $dokumen)->with('status', 'Dokumen dibuka kembali dan berstatus menunggu.');
    }

    public function destroy(TindakLanjut $tindakLanjut): RedirectResponse
    {
        $dokumen = $tindakLanjut->dokumen;
        Gate::authorize('update', $dokumen);

        $this->berkasService->hapus($tindakLanjut->bukti_path);
        $tindakLanjut->delete();
        ActivityLogger::catat('tindak_lanjut.hapus', $dokumen->judul, $dokumen->id);

        return redirect()->route('dokumen.show', $dokumen)->with('status', 'Catatan tindak lanjut dihapus.');
    }

    public function bukti(Request $request, TindakLanjut $tindakLanjut): StreamedResponse
    {
        $dokumen = $tindakLanjut->dokumen;
        Gate::authorize('view', $dokumen);
        abort_unless($this->berkasService->ada($tindakLanjut->bukti_path), 404);

        $nama = 'bukti-' . $dokumen->id . '-' . $tindakLanjut->id . '.' . pathinfo($tindakLanjut->bukti_path, PATHINFO_EXTENSION);

        if ($request->boolean('unduh')) {
            ActivityLogger::catat('tindak_lanjut.unduh', $dokumen->judul, $dokumen->id);

            return $this->berkasService->unduh($tindakLanjut->bukti_path, $nama);
        }

        return $this->berkasService->tampilkan($tindakLanjut->bukti_path, $nama);
    }
}
