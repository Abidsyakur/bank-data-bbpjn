<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class PencarianTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataDasar();

        $pp = $this->buatUser('admin_pp');
        $sp4n = $this->buatUser('admin_sp4n');

        $this->buatDokumen($pp, 'perizinan', ['nomor_surat' => '001/PU/2026', 'judul' => 'Izin Jembatan']);
        $this->buatDokumen($pp, 'konsultasi', ['nomor_surat' => '002/PU/2026', 'judul' => 'Konsultasi Trase']);
        $this->buatDokumen($pp, 'pengaduan_langsung', ['nomor_surat' => 'AD-01', 'judul' => 'Aduan 100% rusak']);
        $this->buatDokumen($pp, 'pengaduan_langsung', ['nomor_surat' => 'AD-02', 'judul' => 'Aduan selesai', 'status' => Dokumen::STATUS_SELESAI]);
        $this->buatDokumen($sp4n, 'rekap_pengaduan_sp4n', ['nomor_surat' => '001/SP/2026', 'judul' => 'Izin Jembatan Rahasia SP4N']);
    }

    private function judulHasil($respons): array
    {
        return $respons->viewData('dokumen')->getCollection()->pluck('judul')->all();
    }

    public function test_cari_berdasarkan_nomor_surat(): void
    {
        $respons = $this->actingAs($this->buatUser('pimpinan'))->get('/dokumen?q=' . urlencode('001/PU'));

        $this->assertSame(['Izin Jembatan'], $this->judulHasil($respons));
    }

    public function test_cari_berdasarkan_sebagian_judul(): void
    {
        $respons = $this->actingAs($this->buatUser('pimpinan'))->get('/dokumen?q=Trase');

        $this->assertSame(['Konsultasi Trase'], $this->judulHasil($respons));
    }

    public function test_pencarian_tanpa_hasil_menampilkan_pesan(): void
    {
        $this->actingAs($this->buatUser('pimpinan'))->get('/dokumen?q=zzzxx')
            ->assertOk()
            ->assertSee('Tidak ada dokumen yang cocok');
    }

    public function test_filter_jenis_layanan(): void
    {
        $respons = $this->actingAs($this->buatUser('pimpinan'))->get('/dokumen?layanan=konsultasi');

        $this->assertSame(['Konsultasi Trase'], $this->judulHasil($respons));
    }

    public function test_filter_status_dan_kata_kunci_digabung(): void
    {
        $this->actingAs($this->buatUser('pimpinan'));

        $this->assertSame(['Aduan 100% rusak'], $this->judulHasil($this->get('/dokumen?status=menunggu&q=Aduan')));
        $this->assertSame(['Aduan selesai'], $this->judulHasil($this->get('/dokumen?status=selesai&q=Aduan')));
    }

    public function test_kombinasi_kata_kunci_dan_layanan(): void
    {
        $respons = $this->actingAs($this->buatUser('pimpinan'))->get('/dokumen?q=Izin&layanan=perizinan');

        $this->assertSame(['Izin Jembatan'], $this->judulHasil($respons));
    }

    public function test_admin_hanya_menemukan_dokumen_layanannya(): void
    {
        $respons = $this->actingAs($this->buatUser('admin_pp', 'pp2'))->get('/dokumen?q=Izin+Jembatan');

        $this->assertSame(['Izin Jembatan'], $this->judulHasil($respons));
        $respons->assertDontSee('Rahasia SP4N');
    }

    public function test_tanda_persen_dan_garis_bawah_dianggap_teks_biasa(): void
    {
        $this->actingAs($this->buatUser('pimpinan'));

        $this->assertSame(['Aduan 100% rusak'], $this->judulHasil($this->get('/dokumen?q=' . urlencode('%'))));
        $this->assertSame([], $this->judulHasil($this->get('/dokumen?q=' . urlencode('_'))));
    }

    public function test_tanda_seru_sebagai_karakter_escape_tidak_merusak_pencarian(): void
    {
        $this->actingAs($this->buatUser('pimpinan'));
        $this->buatDokumen($this->buatUser('admin_pp', 'pp3'), 'perizinan', ['judul' => 'Penting! Segera']);

        $this->assertSame(['Penting! Segera'], $this->judulHasil($this->get('/dokumen?q=' . urlencode('Penting!'))));
    }

    public function test_jumlah_pada_tab_mengikuti_hasil_pencarian(): void
    {
        $respons = $this->actingAs($this->buatUser('pimpinan'))->get('/dokumen?q=Aduan');

        $this->assertSame(['semua' => 2, 'menunggu' => 1, 'selesai' => 1], $respons->viewData('hitung'));
    }

    public function test_kotak_pencarian_cepat_ada_di_header(): void
    {
        $this->actingAs($this->buatUser('admin_pp', 'pp4'))->get('/dashboard')->assertSee('Cari nomor surat atau judul');
    }
}
