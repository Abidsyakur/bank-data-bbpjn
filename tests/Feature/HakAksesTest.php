<?php

namespace Tests\Feature;

use App\Services\BerkasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class HakAksesTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->siapkanDataDasar();
    }

    public static function halamanKhususPimpinan(): array
    {
        $halaman = ['/akun', '/akun/create', '/laporan', '/laporan/pdf', '/laporan/excel', '/backup', '/riwayat'];
        $kasus = [];

        foreach (['admin_pp', 'admin_sp4n', 'admin_jaki'] as $role) {
            foreach ($halaman as $alamat) {
                $kasus["{$role} membuka {$alamat}"] = [$role, $alamat];
            }
        }

        return $kasus;
    }

    #[DataProvider('halamanKhususPimpinan')]
    public function test_admin_tidak_dapat_membuka_halaman_khusus_pimpinan(string $role, string $alamat): void
    {
        $this->actingAs($this->buatUser($role))->get($alamat)->assertForbidden();
    }

    public function test_pimpinan_dapat_membuka_halaman_pengelolaan(): void
    {
        $this->actingAs($this->buatUser('pimpinan'));

        foreach (['/akun', '/akun/create', '/laporan', '/backup', '/riwayat', '/dashboard', '/dokumen'] as $alamat) {
            $this->get($alamat)->assertOk();
        }
    }

    public function test_admin_tidak_dapat_membuka_dokumen_layanan_lain(): void
    {
        $pp = $this->buatUser('admin_pp');
        $dokumenSp4n = $this->buatDokumen($this->buatUser('admin_sp4n'), 'rekap_pengaduan_sp4n', ['judul' => 'Aduan Rahasia SP4N']);

        $this->actingAs($pp);

        $this->get(route('dokumen.show', $dokumenSp4n))->assertForbidden();
        $this->get(route('dokumen.edit', $dokumenSp4n))->assertForbidden();
        $this->put(route('dokumen.update', $dokumenSp4n), [
            'layanan_id' => $this->layanan('perizinan')->id,
            'judul'      => 'Dibajak',
            'tanggal'    => '2026-10-01',
        ])->assertForbidden();
        $this->delete(route('dokumen.destroy', $dokumenSp4n))->assertForbidden();

        $this->assertDatabaseHas('dokumen', ['id' => $dokumenSp4n->id, 'judul' => 'Aduan Rahasia SP4N']);
    }

    public function test_filter_layanan_milik_admin_lain_menghasilkan_404(): void
    {
        $this->actingAs($this->buatUser('admin_pp'))
            ->get('/dokumen?layanan=rekap_pengaduan_sp4n')
            ->assertNotFound();
    }

    public function test_berkas_layanan_lain_tidak_dapat_dibuka_atau_diunduh(): void
    {
        $pp = $this->buatUser('admin_pp');
        $path = app(BerkasService::class)->simpan(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));
        $dokumen = $this->buatDokumen($pp, 'perizinan', ['file_path' => $path]);

        $this->actingAs($this->buatUser('admin_sp4n'));
        $this->get(route('dokumen.berkas', $dokumen))->assertForbidden();
        $this->get(route('dokumen.berkas', [$dokumen, 'unduh' => 1]))->assertForbidden();
    }

    public function test_pimpinan_dapat_membuka_dan_mengunduh_berkas_semua_layanan(): void
    {
        $sp4n = $this->buatUser('admin_sp4n');
        $path = app(BerkasService::class)->simpan(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));
        $dokumen = $this->buatDokumen($sp4n, 'rekap_pengaduan_sp4n', ['file_path' => $path]);

        $this->actingAs($this->buatUser('pimpinan'));
        $this->get(route('dokumen.berkas', $dokumen))->assertOk();
        $this->get(route('dokumen.berkas', [$dokumen, 'unduh' => 1]))->assertOk()->assertDownload();
    }

    public function test_pimpinan_melihat_semua_dokumen_tetapi_tidak_dapat_mengubahnya(): void
    {
        $dokumen = $this->buatDokumen($this->buatUser('admin_pp'), 'perizinan', ['judul' => 'Dokumen Milik PP']);
        $this->buatDokumen($this->buatUser('admin_jaki'), 'laporan_kerusakan_jaki', ['judul' => 'Laporan Milik JAKI']);

        $this->actingAs($this->buatUser('pimpinan'));

        $this->get('/dokumen')
            ->assertOk()
            ->assertSee('Dokumen Milik PP')
            ->assertSee('Laporan Milik JAKI')
            ->assertDontSee('Tambah dokumen');

        $this->get(route('dokumen.create'))->assertForbidden();
        $this->post(route('dokumen.store'), [
            'layanan_id' => $this->layanan('perizinan')->id,
            'judul'      => 'Tidak boleh',
            'tanggal'    => '2026-10-01',
        ])->assertForbidden();
        $this->get(route('dokumen.edit', $dokumen))->assertForbidden();
        $this->delete(route('dokumen.destroy', $dokumen))->assertForbidden();

        $this->assertDatabaseMissing('dokumen', ['judul' => 'Tidak boleh']);
    }

    public function test_berkas_privat_tidak_dapat_diakses_lewat_alamat_langsung(): void
    {
        $this->get('/storage/dokumen/a.pdf')->assertNotFound();

        $path = app(BerkasService::class)->simpan(UploadedFile::fake()->create('a.pdf', 10, 'application/pdf'));
        $dokumen = $this->buatDokumen($this->buatUser('admin_pp'), 'perizinan', ['file_path' => $path]);

        $this->get('/' . $path)->assertNotFound();
        $this->get(route('dokumen.berkas', $dokumen))->assertRedirect('/login');
    }

    public function test_menu_arsip_mengikuti_peran(): void
    {
        $this->actingAs($this->buatUser('admin_pp', 'pp'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Perizinan')->assertSee('Layanan Konsultasi')->assertSee('Pengaduan Langsung')
            ->assertSee('Arsip PPID')->assertSee('Laporan SKM')
            ->assertDontSee('Rekap Pengaduan Online SP4N')
            ->assertDontSee('Manajemen akun');

        $this->actingAs($this->buatUser('admin_sp4n', 'sp'))->get('/dashboard')
            ->assertSee('Rekap Pengaduan Online SP4N')->assertSee('Bukti Tindak Lanjut')->assertSee('Laporan Kinerja SP4N')
            ->assertDontSee('Perizinan');

        $this->actingAs($this->buatUser('admin_jaki', 'jk'))->get('/dashboard')
            ->assertSee('Laporan Kerusakan Jalan/Jembatan')->assertSee('Dokumentasi Penanganan Lapangan')->assertSee('Laporan Kinerja JAKI')
            ->assertDontSee('Layanan Konsultasi');
    }

    public function test_menu_pimpinan_memuat_pengaturan_laporan_dan_backup(): void
    {
        $this->actingAs($this->buatUser('pimpinan'))->get('/dashboard')
            ->assertSee('Manajemen akun')
            ->assertSee('Backup & restore')
            ->assertSee('Riwayat aktivitas')
            ->assertSee('Laporan rekapitulasi');
    }
}
