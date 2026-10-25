<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class LaporanExport implements WithMultipleSheets
{
    public function __construct(private array $laporan)
    {
    }

    public function sheets(): array
    {
        $rekap = $this->laporan['rekap']->map(fn ($l) => [
            $l->nama,
            $l->is_pengaduan ? 'Pengaduan' : 'Arsip',
            $l->total,
            $l->butuh_tindak_lanjut ? $l->menunggu : '-',
            $l->butuh_tindak_lanjut ? $l->selesai : '-',
        ])->all();

        $r = $this->laporan['ringkasan'];
        $rekap[] = ['JUMLAH', '', $r['total'], $r['menunggu'], $r['selesai']];

        $dokumen = $this->laporan['dokumen']->values()->map(fn ($d, $i) => [
            $i + 1,
            $d->tanggal->format('d/m/Y'),
            $d->nomor_surat ?? '-',
            $d->judul,
            $d->layanan->nama,
            $d->labelStatus(),
            $d->user->nama,
        ])->all();

        return [
            new TabelSheet('Rekap', ['Layanan', 'Jenis', 'Total', 'Menunggu', 'Selesai'], $rekap),
            new TabelSheet('Dokumen', ['No', 'Tanggal', 'Nomor surat', 'Judul', 'Layanan', 'Status', 'Diunggah oleh'], $dokumen),
        ];
    }
}
