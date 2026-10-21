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
            'berkas.max'       => 'Ukuran berkas maksimal 10 MB.',
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
        ];
    }
}
