<?php

namespace Tests\Unit;

use App\Support\PesanValidasi;
use PHPUnit\Framework\TestCase;

class PesanValidasiTest extends TestCase
{
    public function test_aturan_utama_memiliki_pesan_bahasa_indonesia(): void
    {
        $pesan = PesanValidasi::pesan();

        foreach (['required', 'unique', 'min', 'max', 'confirmed', 'exists', 'alpha_dash', 'current_password', 'date', 'file', 'mimes', 'after_or_equal'] as $aturan) {
            $this->assertArrayHasKey($aturan, $pesan, "Pesan untuk aturan '{$aturan}' belum ada.");
            $this->assertNotSame('', trim($pesan[$aturan]));
        }
    }

    public function test_pesan_khusus_berkas_menyebut_batas_dan_format(): void
    {
        $pesan = PesanValidasi::pesan();

        $this->assertStringContainsString('10 MB', $pesan['berkas.max']);
        $this->assertStringContainsString('10 MB', $pesan['bukti.max']);
        $this->assertStringContainsString('PDF, JPG, atau PNG', $pesan['mimes']);
        $this->assertStringContainsString('ZIP', $pesan['berkas_backup.mimes']);
        $this->assertStringContainsString('PULIHKAN', $pesan['konfirmasi.in']);
    }

    public function test_pesan_umum_memakai_penampung_nama_atribut(): void
    {
        $pesan = PesanValidasi::pesan();

        foreach (['required', 'unique', 'min', 'max', 'exists', 'alpha_dash', 'date', 'file', 'mimes', 'after_or_equal'] as $aturan) {
            $this->assertStringContainsString(':attribute', $pesan[$aturan], $aturan);
        }
    }

    public function test_nama_atribut_untuk_semua_formulir_tersedia(): void
    {
        $atribut = PesanValidasi::atribut();

        foreach (['nama', 'username', 'password', 'role_id', 'layanan_id', 'nomor_surat', 'judul', 'tanggal', 'berkas', 'catatan', 'bukti', 'dari', 'sampai', 'layanan', 'pengguna', 'aksi'] as $kolom) {
            $this->assertArrayHasKey($kolom, $atribut, "Nama atribut '{$kolom}' belum ada.");
            $this->assertNotSame('', trim($atribut[$kolom]));
        }
    }
}
