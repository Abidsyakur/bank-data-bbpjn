<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataDasar();
    }

    /** 4 Pengaduan Langsung (1 selesai), 2 Perizinan, 1 Rekap Pengaduan SP4N. */
    private function isiData(): void
    {
        $pp = $this->buatUser('admin_pp');
        $sp4n = $this->buatUser('admin_sp4n');

        foreach (range(1, 3) as $i) {
            $this->buatDokumen($pp, 'pengaduan_langsung', ['judul' => "Aduan {$i}"]);
        }
        $this->buatDokumen($pp, 'pengaduan_langsung', ['judul' => 'Aduan selesai', 'status' => Dokumen::STATUS_SELESAI]);
        $this->buatDokumen($pp, 'perizinan', ['judul' => 'Izin 1']);
        $this->buatDokumen($pp, 'perizinan', ['judul' => 'Izin 2']);
        $this->buatDokumen($sp4n, 'rekap_pengaduan_sp4n', ['judul' => 'Rekap 1']);
    }

    public function test_ringkasan_pimpinan_menghitung_seluruh_layanan(): void
    {
        $this->isiData();

        $respons = $this->actingAs($this->buatUser('pimpinan'))->get('/dashboard')->assertOk();
        $ringkasan = $respons->viewData('ringkasan');

        $this->assertSame(7, $ringkasan['total']);
        $this->assertSame(5, $ringkasan['pengaduan']);
        $this->assertSame(3, $ringkasan['menunggu']);
        $this->assertSame(1, $ringkasan['selesai']);
        $this->assertSame(4, $ringkasan['dipantau']);
        $this->assertSame(25, $ringkasan['persen_selesai']);
        $this->assertTrue($ringkasan['ada_status']);
        $respons->assertSee('25%')->assertSee('Menunggu tindak lanjut');
    }

    public function test_status_dan_persentase_hanya_dari_layanan_yang_memiliki_status(): void
    {
        $this->isiData();

        $respons = $this->actingAs($this->buatUser('pimpinan'))->get('/dashboard');

        $this->assertSame(['Pengaduan Langsung'], $respons->viewData('chartStatus')['data']['labels']);
        $this->assertContains('Perizinan', $respons->viewData('chartArsip')['data']['labels']);
        $this->assertNotContains('Pengaduan Langsung', $respons->viewData('chartArsip')['data']['labels']);
    }

    public function test_admin_hanya_melihat_angka_layanannya(): void
    {
        $this->isiData();

        $ringkasan = $this->actingAs($this->buatUser('admin_pp', 'pp2'))->get('/dashboard')->viewData('ringkasan');

        $this->assertSame(6, $ringkasan['total']);
        $this->assertSame(4, $ringkasan['pengaduan']);
    }

    public function test_dashboard_admin_sp4n_tanpa_status_dan_persentase(): void
    {
        $this->isiData();

        $respons = $this->actingAs($this->buatUser('admin_sp4n', 'sp2'))->get('/dashboard')->assertOk();
        $ringkasan = $respons->viewData('ringkasan');

        $this->assertSame(1, $ringkasan['total']);
        $this->assertFalse($ringkasan['ada_status']);
        $this->assertNull($ringkasan['persen_selesai']);
        $this->assertSame([], $respons->viewData('chartStatus')['data']['labels']);
        $respons->assertDontSee('Menunggu tindak lanjut');
    }

    public function test_persentase_tidak_tampil_bila_belum_ada_dokumen_berstatus(): void
    {
        $this->buatDokumen($this->buatUser('admin_pp'), 'perizinan');

        $ringkasan = $this->actingAs($this->buatUser('pimpinan'))->get('/dashboard')->viewData('ringkasan');

        $this->assertNull($ringkasan['persen_selesai']);
        $this->assertSame(0, $ringkasan['dipantau']);
    }

    public function test_grafik_per_bulan_mencakup_12_bulan_dan_menempatkan_dokumen_pada_bulannya(): void
    {
        $pp = $this->buatUser('admin_pp');
        $this->buatDokumen($pp, 'perizinan', ['tanggal' => now()->toDateString()]);
        $this->buatDokumen($pp, 'perizinan', ['tanggal' => now()->toDateString()]);
        $this->buatDokumen($pp, 'perizinan', ['tanggal' => now()->subMonthsNoOverflow(2)->toDateString()]);
        $this->buatDokumen($pp, 'perizinan', ['tanggal' => now()->subMonthsNoOverflow(20)->toDateString()]);

        $data = $this->actingAs($this->buatUser('pimpinan'))->get('/dashboard')
            ->viewData('chartBulan')['data']['datasets'][0]['data'];

        $this->assertCount(12, $data);
        $this->assertSame(2, $data[11], 'Bulan berjalan');
        $this->assertSame(1, $data[9], 'Dua bulan lalu');
        $this->assertSame(3, array_sum($data), 'Dokumen di luar 12 bulan tidak dihitung.');
    }

    public function test_dokumen_yang_dihapus_tidak_dihitung(): void
    {
        $dokumen = $this->buatDokumen($this->buatUser('admin_pp'), 'perizinan');
        $dokumen->delete();

        $this->assertSame(0, $this->actingAs($this->buatUser('pimpinan'))->get('/dashboard')->viewData('ringkasan')['total']);
    }
}
