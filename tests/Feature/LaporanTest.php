<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Services\LaporanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataDasar();

        $pp = $this->buatUser('admin_pp');
        $this->buatDokumen($pp, 'perizinan', ['judul' => 'Izin September', 'tanggal' => '2026-09-15']);
        $this->buatDokumen($pp, 'perizinan', ['judul' => 'Izin Oktober', 'tanggal' => '2026-10-05']);
        $this->buatDokumen($pp, 'pengaduan_langsung', ['judul' => 'Aduan menunggu', 'tanggal' => '2026-10-10']);
        $this->buatDokumen($pp, 'pengaduan_langsung', ['judul' => 'Aduan selesai', 'tanggal' => '2026-10-12', 'status' => Dokumen::STATUS_SELESAI]);
        $this->buatDokumen($this->buatUser('admin_sp4n'), 'rekap_pengaduan_sp4n', ['judul' => 'Rekap Oktober', 'tanggal' => '2026-10-20']);

        $this->actingAs($this->buatUser('pimpinan'));
    }

    public function test_periode_bawaan_adalah_bulan_berjalan(): void
    {
        $filter = $this->get('/laporan')->assertOk()->viewData('filter');

        $this->assertSame(now()->startOfMonth()->toDateString(), $filter['dari']);
        $this->assertSame(now()->toDateString(), $filter['sampai']);
        $this->assertNull($filter['layanan']);
    }

    public function test_filter_periode_membatasi_rekap(): void
    {
        $ringkasan = $this->get('/laporan?dari=2026-10-01&sampai=2026-10-31')->viewData('laporan')['ringkasan'];

        $this->assertSame(4, $ringkasan['total']);
        $this->assertSame(3, $ringkasan['pengaduan']);
        $this->assertSame(1, $ringkasan['menunggu']);
        $this->assertSame(1, $ringkasan['selesai']);
    }

    public function test_tanggal_dikosongkan_berarti_semua_tanggal(): void
    {
        $laporan = $this->get('/laporan?dari=&sampai=')->viewData('laporan');

        $this->assertSame(5, $laporan['ringkasan']['total']);
        $this->assertSame('Semua tanggal', $laporan['periode']);
    }

    public function test_filter_kategori_layanan(): void
    {
        $laporan = $this->get('/laporan?dari=&sampai=&layanan=perizinan')->viewData('laporan');

        $this->assertCount(1, $laporan['rekap']);
        $this->assertSame(2, $laporan['ringkasan']['total']);
        $this->assertSame('Perizinan', $laporan['kategori']);
        $this->assertCount(2, $laporan['dokumen']);
    }

    public function test_tanggal_akhir_lebih_awal_dari_tanggal_mulai_ditolak(): void
    {
        $this->get('/laporan?dari=2026-10-10&sampai=2026-10-01')
            ->assertSessionHasErrors(['sampai' => 'Tanggal akhir tidak boleh lebih awal dari tanggal mulai.']);
    }

    public function test_layanan_yang_tidak_ada_ditolak(): void
    {
        $this->get('/laporan?layanan=ngawur')->assertSessionHasErrors('layanan');
    }

    public function test_rincian_dokumen_terurut_menurut_tanggal(): void
    {
        $laporan = app(LaporanService::class)->bangun(['dari' => null, 'sampai' => null, 'layanan' => null]);

        $this->assertSame(
            ['Izin September', 'Izin Oktober', 'Aduan menunggu', 'Aduan selesai', 'Rekap Oktober'],
            $laporan['dokumen']->pluck('judul')->all()
        );
    }

    public function test_status_hanya_dihitung_dari_layanan_yang_memiliki_status(): void
    {
        $laporan = app(LaporanService::class)->bangun(['dari' => null, 'sampai' => null, 'layanan' => null]);

        $this->assertSame(1, $laporan['ringkasan']['menunggu']);
        $this->assertSame(1, $laporan['ringkasan']['selesai']);
    }

    public function test_unduh_pdf(): void
    {
        $respons = $this->get('/laporan/pdf?dari=2026-10-01&sampai=2026-10-31')->assertOk();

        $this->assertStringContainsString('application/pdf', $respons->headers->get('content-type'));
        $this->assertStringStartsWith('%PDF', $respons->getContent());
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'laporan.pdf']);
    }

    public function test_unduh_excel(): void
    {
        $respons = $this->get('/laporan/excel?dari=2026-10-01&sampai=2026-10-31')->assertOk();

        $this->assertStringContainsString('.xlsx', $respons->headers->get('content-disposition'));
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'laporan.excel']);
    }
}
