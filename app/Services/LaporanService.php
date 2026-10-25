<?php

namespace App\Services;

use App\Models\Dokumen;
use App\Models\Layanan;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;

/**
 * Menyusun data laporan rekapitulasi gabungan.
 * Dipakai bersama oleh pratinjau di layar, PDF, dan Excel agar angkanya selalu sama.
 *
 * Filter: ['dari' => 'Y-m-d'|null, 'sampai' => 'Y-m-d'|null, 'layanan' => kode|null]
 */
class LaporanService
{
    public function bangun(array $f): array
    {
        $terapkan = function (Builder $q) use ($f): Builder {
            return $q
                ->when($f['dari'], fn ($x) => $x->whereDate('tanggal', '>=', $f['dari']))
                ->when($f['sampai'], fn ($x) => $x->whereDate('tanggal', '<=', $f['sampai']));
        };

        $rekap = Layanan::query()
            ->when($f['layanan'], fn ($q) => $q->where('kode', $f['layanan']))
            ->orderBy('nama')
            ->withCount([
                'dokumen as total'    => fn ($q) => $terapkan($q),
                'dokumen as menunggu' => fn ($q) => $terapkan($q)->where('status', Dokumen::STATUS_MENUNGGU),
                'dokumen as selesai'  => fn ($q) => $terapkan($q)->where('status', Dokumen::STATUS_SELESAI),
            ])
            ->get();

        $detail = Dokumen::query()
            ->with(['layanan', 'user'])
            ->when($f['layanan'], fn ($q) => $q->whereHas('layanan', fn ($l) => $l->where('kode', $f['layanan'])));
        $terapkan($detail);
        $dokumen = $detail->orderBy('tanggal')->orderBy('id')->get();

        $pengaduan = $rekap->where('is_pengaduan', true);

        return [
            'filter'    => $f,
            'periode'   => $this->periode($f),
            'kategori'  => $f['layanan'] ? ($rekap->first()?->nama ?? '-') : 'Semua layanan',
            'rekap'     => $rekap,
            'dokumen'   => $dokumen,
            'ringkasan' => [
                'total'     => $rekap->sum('total'),
                'menunggu'  => $rekap->where('butuh_tindak_lanjut', true)->sum('menunggu'),
                'selesai'   => $rekap->where('butuh_tindak_lanjut', true)->sum('selesai'),
                'pengaduan' => $pengaduan->sum('total'),
            ],
        ];
    }

    public function periode(array $f): string
    {
        $fmt = fn (string $d) => Carbon::parse($d)->format('d/m/Y');

        return match (true) {
            (bool) ($f['dari'] && $f['sampai']) => $fmt($f['dari']) . ' - ' . $fmt($f['sampai']),
            (bool) $f['dari']                   => 'Sejak ' . $fmt($f['dari']),
            (bool) $f['sampai']                 => 'Sampai ' . $fmt($f['sampai']),
            default                             => 'Semua tanggal',
        };
    }
}
