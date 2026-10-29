<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\TindakLanjut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class TindakLanjutTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    private User $pp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->siapkanDataDasar();
        $this->pp = $this->buatUser('admin_pp');
    }

    private function aduan(): Dokumen
    {
        return $this->buatDokumen($this->pp, 'pengaduan_langsung', ['judul' => 'Aduan jalan rusak']);
    }

    private function isian(array $ubah = []): array
    {
        return array_merge(['tanggal' => '2026-10-02', 'catatan' => 'Sudah dihubungi pelapor.'], $ubah);
    }

    public function test_formulir_tindak_lanjut_tampil_hanya_pada_pengaduan_langsung(): void
    {
        $this->actingAs($this->pp);

        $this->get(route('dokumen.show', $this->aduan()))->assertSee('Tambah tindak lanjut');
        $this->get(route('dokumen.show', $this->buatDokumen($this->pp, 'perizinan')))->assertDontSee('Tambah tindak lanjut');
    }

    public function test_tambah_tindak_lanjut_tanpa_menandai_selesai(): void
    {
        $dokumen = $this->aduan();

        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $dokumen), $this->isian())
            ->assertRedirect(route('dokumen.show', $dokumen))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('tindak_lanjut', ['dokumen_id' => $dokumen->id, 'user_id' => $this->pp->id, 'catatan' => 'Sudah dihubungi pelapor.']);
        $this->assertSame(Dokumen::STATUS_MENUNGGU, $dokumen->fresh()->status);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'tindak_lanjut.tambah', 'dokumen_id' => $dokumen->id]);
    }

    public function test_tambah_tindak_lanjut_dengan_bukti_dan_tandai_selesai(): void
    {
        $dokumen = $this->aduan();
        $bukti = UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf');

        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $dokumen), $this->isian(['bukti' => $bukti, 'selesai' => '1']))
            ->assertSessionHasNoErrors();

        $tindakLanjut = TindakLanjut::firstOrFail();
        $this->assertStringStartsWith('tindak-lanjut/', $tindakLanjut->bukti_path);
        Storage::disk('local')->assertExists($tindakLanjut->bukti_path);
        $this->assertSame(Dokumen::STATUS_SELESAI, $dokumen->fresh()->status);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'dokumen.selesai', 'dokumen_id' => $dokumen->id]);
    }

    public function test_catatan_wajib_diisi(): void
    {
        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $this->aduan()), $this->isian(['catatan' => '']))
            ->assertSessionHasErrors(['catatan' => 'Catatan wajib diisi.']);

        $this->assertDatabaseCount('tindak_lanjut', 0);
    }

    public function test_format_bukti_yang_tidak_diizinkan_ditolak(): void
    {
        $docx = UploadedFile::fake()->create('bukti.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $this->aduan()), $this->isian(['bukti' => $docx]))
            ->assertSessionHasErrors(['bukti' => 'Bukti harus berformat PDF, JPG, atau PNG.']);
    }

    public function test_dokumen_selesai_tidak_dapat_ditambah_tindak_lanjut_sebelum_dibuka_kembali(): void
    {
        $dokumen = $this->aduan();
        $dokumen->update(['status' => Dokumen::STATUS_SELESAI]);

        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $dokumen), $this->isian())->assertForbidden();
        $this->assertDatabaseCount('tindak_lanjut', 0);
    }

    public function test_buka_kembali_mengembalikan_status_menunggu(): void
    {
        $dokumen = $this->aduan();
        $dokumen->update(['status' => Dokumen::STATUS_SELESAI]);

        $this->actingAs($this->pp)->patch(route('tindak-lanjut.buka-kembali', $dokumen))->assertRedirect(route('dokumen.show', $dokumen));

        $this->assertSame(Dokumen::STATUS_MENUNGGU, $dokumen->fresh()->status);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'dokumen.buka_kembali', 'dokumen_id' => $dokumen->id]);
    }

    public function test_hapus_catatan_tindak_lanjut_juga_menghapus_buktinya(): void
    {
        $dokumen = $this->aduan();
        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $dokumen), $this->isian([
            'bukti' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ]));
        $tindakLanjut = TindakLanjut::firstOrFail();

        $this->delete(route('tindak-lanjut.destroy', $tindakLanjut))->assertRedirect(route('dokumen.show', $dokumen));

        $this->assertDatabaseMissing('tindak_lanjut', ['id' => $tindakLanjut->id]);
        Storage::disk('local')->assertMissing($tindakLanjut->bukti_path);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'tindak_lanjut.hapus']);
    }

    public function test_layanan_arsip_tidak_menerima_tindak_lanjut(): void
    {
        $this->actingAs($this->pp)
            ->post(route('tindak-lanjut.store', $this->buatDokumen($this->pp, 'perizinan')), $this->isian())
            ->assertNotFound();
    }

    public function test_pimpinan_hanya_dapat_melihat_tindak_lanjut(): void
    {
        $dokumen = $this->aduan();
        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $dokumen), $this->isian());

        $this->actingAs($this->buatUser('pimpinan'));
        $this->get(route('dokumen.show', $dokumen))
            ->assertOk()
            ->assertSee('Sudah dihubungi pelapor.')
            ->assertDontSee('Tambah tindak lanjut');
        $this->post(route('tindak-lanjut.store', $dokumen), $this->isian())->assertForbidden();
        $this->patch(route('tindak-lanjut.buka-kembali', $dokumen))->assertForbidden();
    }

    public function test_admin_lain_tidak_dapat_mengelola_atau_membuka_bukti(): void
    {
        $dokumen = $this->aduan();
        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $dokumen), $this->isian([
            'bukti' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ]));
        $tindakLanjut = TindakLanjut::firstOrFail();

        $this->actingAs($this->buatUser('admin_sp4n'));
        $this->post(route('tindak-lanjut.store', $dokumen), $this->isian())->assertForbidden();
        $this->get(route('tindak-lanjut.bukti', $tindakLanjut))->assertForbidden();
        $this->delete(route('tindak-lanjut.destroy', $tindakLanjut))->assertForbidden();
    }

    public function test_pemilik_dapat_membuka_dan_mengunduh_bukti(): void
    {
        $dokumen = $this->aduan();
        $this->actingAs($this->pp)->post(route('tindak-lanjut.store', $dokumen), $this->isian([
            'bukti' => UploadedFile::fake()->create('bukti.pdf', 50, 'application/pdf'),
        ]));
        $tindakLanjut = TindakLanjut::firstOrFail();

        $this->get(route('tindak-lanjut.bukti', $tindakLanjut))->assertOk();
        $this->get(route('tindak-lanjut.bukti', [$tindakLanjut, 'unduh' => 1]))->assertOk()->assertDownload();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'tindak_lanjut.unduh', 'dokumen_id' => $dokumen->id]);
    }

    public function test_tab_status_menghitung_dokumen_dengan_benar(): void
    {
        $this->buatDokumen($this->pp, 'pengaduan_langsung', ['judul' => 'A']);
        $this->buatDokumen($this->pp, 'pengaduan_langsung', ['judul' => 'B']);
        $this->buatDokumen($this->pp, 'pengaduan_langsung', ['judul' => 'C', 'status' => Dokumen::STATUS_SELESAI]);

        $respons = $this->actingAs($this->pp)->get('/dokumen?layanan=pengaduan_langsung');

        $this->assertSame(['semua' => 3, 'menunggu' => 2, 'selesai' => 1], $respons->viewData('hitung'));
        $respons->assertSee('Menunggu')->assertSee('Selesai');

        $this->get('/dokumen?layanan=perizinan')->assertDontSee('Filter status');
    }
}
