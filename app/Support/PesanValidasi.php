<?php

namespace App\Support;

class PesanValidasi
{
    public static function pesan(): array
    {
        return [
            'required'         => ':attribute wajib diisi.',
            'unique'           => ':attribute sudah dipakai.',
            'min'              => ':attribute minimal :min karakter.',
            'max'              => ':attribute maksimal :max karakter.',
            'confirmed'        => 'Konfirmasi :attribute tidak cocok.',
            'exists'           => ':attribute tidak valid.',
            'alpha_dash'       => ':attribute hanya boleh berisi huruf, angka, strip, dan garis bawah.',
            'current_password' => 'Kata sandi saat ini salah.',
            'date'             => ':attribute bukan tanggal yang valid.',
            'file'             => ':attribute harus berupa berkas.',
            'mimes'            => ':attribute harus berformat PDF, JPG, atau PNG.',
            'uploaded'         => 'Berkas gagal diunggah. Pastikan ukurannya tidak melebihi 10 MB.',
            'after_or_equal'   => ':attribute tidak boleh lebih awal dari tanggal mulai.',
            'in'               => ':attribute tidak valid.',
            'integer'          => ':attribute tidak valid.',
            'berkas.max'       => 'Ukuran berkas maksimal 10 MB.',
            'berkas_backup.mimes'    => 'Berkas backup harus berformat ZIP.',
            'berkas_backup.max'      => 'Ukuran berkas backup terlalu besar.',
            'berkas_backup.uploaded' => 'Berkas gagal diunggah. Periksa batas upload_max_filesize dan post_max_size di php.ini.',
            'konfirmasi.in'          => 'Ketik PULIHKAN persis seperti contoh.',
            'bukti.max'        => 'Ukuran bukti maksimal 10 MB.',
        ];
    }

    public static function atribut(): array
    {
        return [
            'nama'             => 'Nama',
            'username'         => 'Username',
            'password'         => 'Kata sandi',
            'role_id'          => 'Peran',
            'current_password' => 'Kata sandi saat ini',
            'layanan_id'       => 'Layanan',
            'nomor_surat'      => 'Nomor surat',
            'judul'            => 'Judul',
            'tanggal'          => 'Tanggal',
            'berkas'           => 'Berkas',
            'berkas_backup'    => 'Berkas backup',
            'konfirmasi'       => 'Konfirmasi',
            'dari'             => 'Tanggal mulai',
            'sampai'           => 'Tanggal akhir',
            'layanan'          => 'Kategori layanan',
            'pengguna'         => 'Pengguna',
            'aksi'             => 'Jenis aktivitas',
            'catatan'          => 'Catatan',
            'bukti'            => 'Bukti',
        ];
    }
}
