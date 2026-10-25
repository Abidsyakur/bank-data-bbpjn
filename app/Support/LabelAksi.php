<?php

namespace App\Support;

/** Nama aksi pada log_aktivitas dalam bahasa yang mudah dibaca. */
class LabelAksi
{
    public const DAFTAR = [
        'login'                 => 'Masuk (login)',
        'logout'                => 'Keluar (logout)',
        'ganti_sandi'           => 'Ganti kata sandi',
        'akun.tambah'           => 'Tambah akun',
        'akun.ubah'             => 'Ubah akun',
        'akun.aktifkan'         => 'Aktifkan akun',
        'akun.nonaktifkan'      => 'Nonaktifkan akun',
        'dokumen.tambah'        => 'Tambah dokumen',
        'dokumen.ubah'          => 'Ubah dokumen',
        'dokumen.ganti_berkas'  => 'Ganti berkas dokumen',
        'dokumen.hapus'         => 'Hapus dokumen',
        'dokumen.unduh'         => 'Unduh berkas dokumen',
        'dokumen.selesai'       => 'Tandai dokumen selesai',
        'dokumen.buka_kembali'  => 'Buka kembali dokumen',
        'tindak_lanjut.tambah'  => 'Tambah tindak lanjut',
        'tindak_lanjut.hapus'   => 'Hapus tindak lanjut',
        'tindak_lanjut.unduh'   => 'Unduh bukti tindak lanjut',
        'laporan.pdf'           => 'Unduh laporan PDF',
        'laporan.excel'         => 'Unduh laporan Excel',
        'backup.buat'           => 'Buat backup',
        'backup.unduh'          => 'Unduh backup',
        'backup.hapus'          => 'Hapus backup',
        'backup.unggah'         => 'Unggah backup',
        'restore.jalankan'      => 'Pulihkan data (restore)',
    ];

    /** Aksi yang perlu mendapat perhatian lebih saat meninjau log. */
    public const PENTING = [
        'dokumen.hapus', 'tindak_lanjut.hapus', 'backup.hapus', 'akun.nonaktifkan', 'restore.jalankan',
    ];

    public static function label(string $aksi): string
    {
        return self::DAFTAR[$aksi] ?? $aksi;
    }

    public static function penting(string $aksi): bool
    {
        return in_array($aksi, self::PENTING, true);
    }
}
