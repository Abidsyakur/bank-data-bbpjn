<?php

namespace Tests\Unit;

use App\Support\LabelAksi;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class LabelAksiTest extends TestCase
{
    public function test_aksi_dikenal_menghasilkan_label_bahasa_indonesia(): void
    {
        $this->assertSame('Masuk (login)', LabelAksi::label('login'));
        $this->assertSame('Hapus dokumen', LabelAksi::label('dokumen.hapus'));
        $this->assertSame('Pulihkan data (restore)', LabelAksi::label('restore.jalankan'));
    }

    public function test_aksi_tidak_dikenal_ditampilkan_apa_adanya(): void
    {
        $this->assertSame('aksi.baru', LabelAksi::label('aksi.baru'));
    }

    public function test_aksi_sensitif_ditandai_penting(): void
    {
        foreach (['dokumen.hapus', 'tindak_lanjut.hapus', 'backup.hapus', 'akun.nonaktifkan', 'restore.jalankan'] as $aksi) {
            $this->assertTrue(LabelAksi::penting($aksi), $aksi);
        }
        $this->assertFalse(LabelAksi::penting('login'));
        $this->assertFalse(LabelAksi::penting('dokumen.tambah'));
    }

    public function test_semua_aksi_penting_terdaftar_dan_label_unik(): void
    {
        foreach (LabelAksi::PENTING as $aksi) {
            $this->assertArrayHasKey($aksi, LabelAksi::DAFTAR);
        }
        $this->assertSame(count(LabelAksi::DAFTAR), count(array_unique(LabelAksi::DAFTAR)), 'Label harus unik.');
    }

    /**
     * Mencegah aksi baru lupa diberi label: memindai kode aplikasi untuk semua aksi
     * yang dicatat lewat ActivityLogger::catat() dan memastikan ada labelnya.
     */
    public function test_semua_aksi_yang_dicatat_aplikasi_memiliki_label(): void
    {
        $akar = dirname(__DIR__, 2) . '/app';
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($akar, FilesystemIterator::SKIP_DOTS));

        $ditemukan = [];
        foreach ($iterator as $berkas) {
            if ($berkas->getExtension() !== 'php') {
                continue;
            }
            $isi = file_get_contents($berkas->getPathname());

            preg_match_all('/ActivityLogger::catat\(\s*\'([a-z_.]+)\'/', $isi, $langsung);
            preg_match_all('/\$aksi\s*=\s*[^;]*?\'([a-z_.]+)\'\s*:\s*\'([a-z_.]+)\'/', $isi, $kondisi);

            $ditemukan = array_merge($ditemukan, $langsung[1], $kondisi[1], $kondisi[2]);
        }
        $ditemukan = array_values(array_unique($ditemukan));

        $this->assertNotEmpty($ditemukan, 'Tidak ada aksi log yang terdeteksi; pola pemindaian perlu diperbarui.');
        foreach ($ditemukan as $aksi) {
            $this->assertArrayHasKey($aksi, LabelAksi::DAFTAR, "Aksi '{$aksi}' dicatat aplikasi tetapi belum punya label.");
        }
    }
}
