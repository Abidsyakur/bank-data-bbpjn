<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\User;
use App\Services\BackupService;
use App\Services\BerkasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;
use ZipArchive;

/**
 * Backup dan restore memakai SET FOREIGN_KEY_CHECKS (MySQL/MariaDB), sehingga hanya dijalankan di sana:
 *   set DB_CONNECTION=mysql
 *   set DB_DATABASE=bank_data_bbpjn_test
 *   php artisan test --filter=BackupTest
 */
class BackupTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    private User $pimpinan;
    private User $pp;
    private BackupService $backup;

    protected function setUp(): void
    {
        parent::setUp();

        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->markTestSkipped('Backup dan restore hanya diuji pada MySQL atau MariaDB.');
        }

        Storage::fake('local');
        $this->siapkanDataDasar();
        $this->pimpinan = $this->buatUser('pimpinan');
        $this->pp = $this->buatUser('admin_pp');
        $this->backup = app(BackupService::class);
    }

    private function berkas(string $nama = 'a.pdf'): string
    {
        return app(BerkasService::class)->simpan(UploadedFile::fake()->create($nama, 10, 'application/pdf'));
    }

    private function buatZip(string $nama, array $isi): void
    {
        Storage::disk('local')->makeDirectory('backup');
        $zip = new ZipArchive();
        $zip->open(Storage::disk('local')->path("backup/{$nama}"), ZipArchive::CREATE);
        foreach ($isi as $jalur => $konten) {
            $zip->addFromString($jalur, $konten);
        }
        $zip->close();
    }

    private function ubahIsiZip(string $nama, string $jalur, callable $ubah): void
    {
        $zip = new ZipArchive();
        $zip->open(Storage::disk('local')->path("backup/{$nama}"));
        $zip->addFromString($jalur, $ubah((string) $zip->getFromName($jalur)));
        $zip->close();
    }

    public function test_backup_memuat_manifest_database_dan_berkas(): void
    {
        $this->buatDokumen($this->pp, 'perizinan', ['file_path' => $this->berkas()]);

        $nama = $this->backup->buat('backup', $this->pimpinan);

        $this->assertMatchesRegularExpression('/^backup-\d{8}-\d{6}\.zip$/', $nama);
        $zip = new ZipArchive();
        $this->assertTrue($zip->open(Storage::disk('local')->path("backup/{$nama}")));
        $this->assertNotFalse($zip->locateName('manifest.json'));
        $this->assertNotFalse($zip->locateName('database.ndjson'));

        $daftar = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $daftar[] = $zip->getNameIndex($i);
        }
        $this->assertNotEmpty(array_filter($daftar, fn ($n) => str_starts_with($n, 'berkas/dokumen/')));

        $manifest = json_decode($zip->getFromName('manifest.json'), true);
        $this->assertSame('bank-data-bbpjn', $manifest['aplikasi']);
        $this->assertSame(1, $manifest['tabel']['dokumen']['jumlah']);
        $this->assertSame(1, $manifest['berkas']['jumlah']);
        $this->assertSame(hash('sha256', $zip->getFromName('database.ndjson')), $manifest['sha256_database']);
    }

    public function test_pemulihan_mengembalikan_data_dan_berkas_ke_kondisi_backup(): void
    {
        $path = $this->berkas();
        $awal = $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Dokumen Awal', 'file_path' => $path]);
        $nama = $this->backup->buat('backup', $this->pimpinan);

        $this->buatDokumen($this->pp, 'konsultasi', ['judul' => 'Dibuat Sesudah Backup']);
        Storage::disk('local')->delete($path);

        $pengaman = $this->backup->pulihkan($nama, $this->pimpinan);

        $this->assertStringStartsWith('pra-restore-', $pengaman);
        $this->assertSame(['Dokumen Awal'], Dokumen::withTrashed()->pluck('judul')->all());
        $this->assertNotNull(Dokumen::find($awal->id));
        Storage::disk('local')->assertExists($path);
        Storage::disk('local')->assertExists("backup/{$pengaman}");
    }

    public function test_backup_pengaman_dapat_dipakai_untuk_maju_kembali(): void
    {
        $nama = $this->backup->buat('backup', $this->pimpinan);
        $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Data Terbaru']);

        $pengaman = $this->backup->pulihkan($nama, $this->pimpinan);
        $this->assertSame(0, Dokumen::count());

        $this->backup->pulihkan($pengaman, $this->pimpinan);
        $this->assertSame(['Data Terbaru'], Dokumen::pluck('judul')->all());
    }

    public function test_checksum_yang_rusak_menggagalkan_pemulihan_tanpa_mengubah_data(): void
    {
        $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Awal']);
        $nama = $this->backup->buat('backup', $this->pimpinan);
        $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Sesudah']);
        $this->ubahIsiZip($nama, 'database.ndjson', fn ($isi) => $isi . "\n{\"t\":\"dokumen\",\"r\":{}}");

        try {
            $this->backup->pulihkan($nama, $this->pimpinan);
            $this->fail('Pemulihan seharusnya gagal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('checksum', $e->getMessage());
        }

        $this->assertSame(2, Dokumen::count());
    }

    public function test_kegagalan_di_tengah_pemulihan_membatalkan_seluruh_perubahan(): void
    {
        $path = $this->berkas();
        $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Awal', 'file_path' => $path]);
        $nama = $this->backup->buat('backup', $this->pimpinan);
        $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Sesudah Backup']);

        // Manifest dibuat tidak cocok dengan isi sebenarnya, sehingga pemeriksaan jumlah baris gagal setelah data diganti.
        $this->ubahIsiZip($nama, 'manifest.json', function ($isi) {
            $manifest = json_decode($isi, true);
            $manifest['tabel']['dokumen']['jumlah'] += 1;

            return json_encode($manifest);
        });

        try {
            $this->backup->pulihkan($nama, $this->pimpinan);
            $this->fail('Pemulihan seharusnya gagal.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('tidak cocok', $e->getMessage());
        }

        $this->assertEqualsCanonicalizing(['Awal', 'Sesudah Backup'], Dokumen::pluck('judul')->all());
        Storage::disk('local')->assertExists($path);
    }

    public function test_zip_berisi_jalur_berbahaya_ditolak(): void
    {
        $this->buatZip('uji-evil.zip', ['../evil.txt' => 'x', 'manifest.json' => '{}']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('jalur');
        $this->backup->periksa('uji-evil.zip');
    }

    public function test_zip_yang_bukan_backup_aplikasi_ditolak(): void
    {
        $this->buatZip('uji-palsu.zip', ['manifest.json' => '{"aplikasi":"lain"}', 'database.ndjson' => '']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('bukan backup');
        $this->backup->periksa('uji-palsu.zip');
    }

    public function test_berkas_yang_bukan_zip_ditolak(): void
    {
        Storage::disk('local')->put('backup/uji-rusak.zip', 'ini bukan zip');

        $this->expectException(RuntimeException::class);
        $this->backup->periksa('uji-rusak.zip');
    }

    public function test_nama_berkas_di_luar_pola_dianggap_tidak_ada(): void
    {
        $this->assertFalse($this->backup->ada('../../.env'));
        $this->assertFalse($this->backup->ada('backup.txt'));
        $this->assertFalse($this->backup->ada('tidak-ada.zip'));
    }

    public function test_alur_http_buat_unduh_dan_hapus_backup(): void
    {
        $this->actingAs($this->pimpinan);

        $this->post(route('backup.store'))->assertRedirect(route('backup.index'))->assertSessionHas('status');
        $nama = basename(Storage::disk('local')->files('backup')[0]);

        $this->get(route('backup.index'))->assertOk()->assertSee($nama);
        $this->get(route('backup.unduh', $nama))->assertOk()->assertDownload();
        $this->assertDatabaseHas('backup', ['jenis' => 'backup', 'file_path' => $nama]);

        $this->delete(route('backup.destroy', $nama))->assertRedirect(route('backup.index'));
        $this->assertSame([], Storage::disk('local')->files('backup'));
    }

    public function test_jalur_berbahaya_pada_alamat_menghasilkan_404(): void
    {
        $this->actingAs($this->pimpinan)->get('/backup/..%2F..%2F.env/unduh')->assertNotFound();
    }

    public function test_konfirmasi_pemulihan_menolak_isian_yang_salah(): void
    {
        $nama = $this->backup->buat('backup', $this->pimpinan);
        $this->actingAs($this->pimpinan);

        $this->get(route('backup.konfirmasi', $nama))->assertOk()->assertSee('PULIHKAN');

        $this->post(route('backup.pulihkan', $nama), ['konfirmasi' => 'salah', 'password' => 'password123'])
            ->assertSessionHasErrors(['konfirmasi' => 'Ketik PULIHKAN persis seperti contoh.']);

        $this->post(route('backup.pulihkan', $nama), ['konfirmasi' => 'PULIHKAN', 'password' => 'salah'])
            ->assertSessionHasErrors('password');
    }

    public function test_pemulihan_lewat_http_berhasil_dan_tercatat(): void
    {
        $nama = $this->backup->buat('backup', $this->pimpinan);
        $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Akan Hilang']);

        $this->actingAs($this->pimpinan)
            ->post(route('backup.pulihkan', $nama), ['konfirmasi' => 'PULIHKAN', 'password' => 'password123'])
            ->assertRedirect(route('backup.index'))
            ->assertSessionHas('status');

        $this->assertSame(0, Dokumen::count());
        $this->assertDatabaseHas('backup', ['jenis' => 'restore', 'file_path' => $nama]);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'restore.jalankan', 'user_id' => $this->pimpinan->id]);
    }

    public function test_unggah_berkas_bukan_zip_ditolak(): void
    {
        $this->actingAs($this->pimpinan)
            ->post(route('backup.unggah'), ['berkas_backup' => UploadedFile::fake()->create('x.pdf', 10, 'application/pdf')])
            ->assertSessionHasErrors(['berkas_backup' => 'Berkas backup harus berformat ZIP.']);
    }

    public function test_unggah_zip_palsu_ditolak_dan_tidak_disimpan(): void
    {
        $this->actingAs($this->pimpinan)
            ->post(route('backup.unggah'), ['berkas_backup' => UploadedFile::fake()->create('palsu.zip', 10, 'application/zip')])
            ->assertSessionHasErrors('berkas_backup');

        $this->assertSame([], Storage::disk('local')->files('backup'));
    }

    public function test_unggah_backup_valid_mengarah_ke_halaman_konfirmasi(): void
    {
        $nama = $this->backup->buat('backup', $this->pimpinan);
        $isi = Storage::disk('local')->get("backup/{$nama}");

        $this->actingAs($this->pimpinan)
            ->post(route('backup.unggah'), ['berkas_backup' => UploadedFile::fake()->createWithContent('hasil-unduh.zip', $isi)])
            ->assertRedirectContains('/pulihkan');
    }
}
