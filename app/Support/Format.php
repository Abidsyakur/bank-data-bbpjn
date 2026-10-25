<?php

namespace App\Support;

class Format
{
    /** Ukuran berkas yang mudah dibaca (tanpa ekstensi intl). Contoh: 1,5 MB */
    public static function ukuran(int|float $bytes): string
    {
        $satuan = ['B', 'KB', 'MB', 'GB', 'TB'];
        $nilai = (float) $bytes;
        $i = 0;

        while ($nilai >= 1024 && $i < count($satuan) - 1) {
            $nilai /= 1024;
            $i++;
        }

        return number_format($nilai, $i === 0 ? 0 : 1, ',', '.') . ' ' . $satuan[$i];
    }
}
