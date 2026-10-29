<?php

namespace Tests\Unit;

use App\Services\BerkasService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BerkasServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
    }

    public function test_simpan_memakai_nama_acak_dan_folder_tahun_bulan(): void
    {
        $path = (new BerkasService())->simpan(UploadedFile::fake()->create('rahasia.pdf', 10, 'application/pdf'));

        $this->assertMatchesRegularExpression('#^dokumen/\d{4}/\d{2}/[0-9a-f-]{36}\.pdf$#', $path);
        $this->assertStringNotContainsString('rahasia', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_simpan_dapat_menentukan_folder(): void
    {
        $path = (new BerkasService())->simpan(UploadedFile::fake()->create('bukti.pdf', 10, 'application/pdf'), 'tindak-lanjut');

        $this->assertStringStartsWith('tindak-lanjut/', $path);
        Storage::disk('local')->assertExists($path);
    }

    public function test_hapus_menghapus_berkas_dan_aman_untuk_nilai_kosong(): void
    {
        $service = new BerkasService();
        $path = $service->simpan(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));

        $service->hapus($path);
        Storage::disk('local')->assertMissing($path);

        $service->hapus(null);
        $this->assertTrue(true);
    }

    public function test_ada_memeriksa_keberadaan_berkas(): void
    {
        $service = new BerkasService();
        $path = $service->simpan(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));

        $this->assertTrue($service->ada($path));
        $this->assertFalse($service->ada('dokumen/tidak-ada.pdf'));
        $this->assertFalse($service->ada(null));
    }
}
