<?php

namespace Tests\Unit;

use App\Services\BackupService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

/** Menguji penyaring jalur di dalam ZIP backup (pencegahan Zip Slip). */
class BackupServiceNamaAmanTest extends TestCase
{
    private function namaAman(string|false $nama): bool
    {
        $metode = new ReflectionMethod(BackupService::class, 'namaAman');

        return $metode->invoke(new BackupService(), $nama);
    }

    public static function jalurAman(): array
    {
        return [
            'manifest'      => ['manifest.json'],
            'database'      => ['database.ndjson'],
            'folder berkas' => ['berkas/'],
            'dokumen'       => ['berkas/dokumen/2026/10/a.pdf'],
            'tindak lanjut' => ['berkas/tindak-lanjut/2026/10/b.png'],
        ];
    }

    public static function jalurBerbahaya(): array
    {
        return [
            'kosong'              => [''],
            'naik direktori'      => ['../evil.txt'],
            'naik di tengah'      => ['berkas/../../.env'],
            'jalur mutlak'        => ['/etc/passwd'],
            'garis miring balik'  => ['berkas\\dokumen\\a.pdf'],
            'huruf drive Windows' => ['C:evil.txt'],
            'folder tidak dikenal' => ['lain/file.txt'],
            'subfolder berkas lain' => ['berkas/lain/a.pdf'],
            'menyelinap manifest' => ['manifest.json/../x'],
        ];
    }

    #[DataProvider('jalurAman')]
    public function test_jalur_yang_diizinkan_diterima(string $nama): void
    {
        $this->assertTrue($this->namaAman($nama));
    }

    #[DataProvider('jalurBerbahaya')]
    public function test_jalur_berbahaya_ditolak(string $nama): void
    {
        $this->assertFalse($this->namaAman($nama));
    }

    public function test_nama_tidak_valid_dari_zip_ditolak(): void
    {
        $this->assertFalse($this->namaAman(false));
    }
}
