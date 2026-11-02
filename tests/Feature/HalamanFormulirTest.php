<?php

namespace Tests\Feature;

use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/** Halaman formulir dan perilaku kecil yang belum tersentuh tes lain. */
class HalamanFormulirTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->siapkanDataDasar();
    }

    public function test_halaman_ganti_kata_sandi_dapat_dibuka(): void
    {
        $this->actingAs($this->buatUser('admin_pp'))->get('/ganti-sandi')->assertOk()->assertSee('Kata sandi saat ini');
    }

    public function test_formulir_tambah_dokumen_memilih_layanan_dari_alamat(): void
    {
        $respons = $this->actingAs($this->buatUser('admin_pp'))
            ->get(route('dokumen.create', ['layanan' => 'perizinan']))
            ->assertOk();

        $this->assertSame($this->layanan('perizinan')->id, $respons->viewData('terpilih'));
    }

    public function test_formulir_ubah_dokumen_menampilkan_data_yang_ada(): void
    {
        $pp = $this->buatUser('admin_pp');
        $dokumen = $this->buatDokumen($pp, 'perizinan', ['judul' => 'Judul Untuk Diubah']);

        $this->actingAs($pp)->get(route('dokumen.edit', $dokumen))->assertOk()->assertSee('Judul Untuk Diubah');
    }

    public function test_formulir_akun_dapat_dibuka_pimpinan(): void
    {
        $this->actingAs($this->buatUser('pimpinan'));
        $akun = $this->buatUser('admin_jaki');

        $this->get(route('akun.create'))->assertOk();
        $this->get(route('akun.edit', $akun))->assertOk()->assertSee($akun->username);
    }

    public function test_relasi_layanan_ke_peran_dan_dokumen(): void
    {
        $layanan = $this->layanan('perizinan');
        $this->buatDokumen($this->buatUser('admin_pp'), 'perizinan');

        $this->assertSame('admin_pp', $layanan->role->kode);
        $this->assertSame(1, $layanan->dokumen()->count());
    }

    public function test_pencatat_log_tidak_menulis_bila_tidak_ada_pengguna(): void
    {
        ActivityLogger::catat('login');

        $this->assertDatabaseCount('log_aktivitas', 0);
    }

    public function test_pencatat_log_memotong_keterangan_hingga_255_karakter(): void
    {
        $user = $this->buatUser('admin_pp');

        ActivityLogger::catat('login', str_repeat('a', 400), null, $user->id);

        $this->assertSame(255, strlen(DB::table('log_aktivitas')->value('keterangan')));
    }
}
