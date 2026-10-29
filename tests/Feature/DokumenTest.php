<?php

namespace Tests\Feature;

use App\Models\Dokumen;
use App\Models\User;
use App\Services\BerkasService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class DokumenTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    private User $pp;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        $this->siapkanDataDasar();
        $this->pp = $this->buatUser('admin_pp');
        $this->actingAs($this->pp);
    }

    private function data(string $layanan = 'perizinan', array $ubah = []): array
    {
        return array_merge([
            'layanan_id'  => $this->layanan($layanan)->id,
            'nomor_surat' => '001/PU/2026',
            'judul'       => 'Izin Uji',
            'tanggal'     => '2026-10-01',
        ], $ubah);
    }

    private function pdf(string $nama = 'surat.pdf', int $kb = 100): UploadedFile
    {
        return UploadedFile::fake()->create($nama, $kb, 'application/pdf');
    }

    private function dokumenBerberkas(string $layanan = 'perizinan'): Dokumen
    {
        $path = app(BerkasService::class)->simpan($this->pdf('lama.pdf'));

        return $this->buatDokumen($this->pp, $layanan, ['file_path' => $path]);
    }

    public function test_tambah_dokumen_dengan_berkas_pdf(): void
    {
        $respons = $this->post(route('dokumen.store'), $this->data('perizinan', ['berkas' => $this->pdf()]));

        $dokumen = Dokumen::firstOrFail();
        $respons->assertRedirect(route('dokumen.show', $dokumen))->assertSessionHas('status');

        $this->assertStringStartsWith('dokumen/', $dokumen->file_path);
        $this->assertStringEndsWith('.pdf', $dokumen->file_path);
        Storage::disk('local')->assertExists($dokumen->file_path);
        $this->assertSame($this->pp->id, $dokumen->user_id);
        $this->assertNull($dokumen->status, 'Layanan arsip tidak memiliki status.');
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'dokumen.tambah', 'dokumen_id' => $dokumen->id, 'user_id' => $this->pp->id]);
    }

    public function test_tambah_dokumen_dengan_gambar_jpg_dan_png(): void
    {
        $this->post(route('dokumen.store'), $this->data('perizinan', ['judul' => 'Foto', 'berkas' => UploadedFile::fake()->image('foto.jpg')]))->assertSessionHasNoErrors();
        $this->post(route('dokumen.store'), $this->data('perizinan', ['judul' => 'Gambar', 'berkas' => UploadedFile::fake()->image('gambar.png')]))->assertSessionHasNoErrors();

        $this->assertMatchesRegularExpression('/\.jpe?g$/', Dokumen::where('judul', 'Foto')->value('file_path'));
        $this->assertStringEndsWith('.png', Dokumen::where('judul', 'Gambar')->value('file_path'));
    }

    public function test_dokumen_boleh_tanpa_berkas_dan_tanpa_nomor_surat(): void
    {
        $this->post(route('dokumen.store'), $this->data('perizinan', ['nomor_surat' => null]))->assertSessionHasNoErrors();

        $dokumen = Dokumen::firstOrFail();
        $this->assertNull($dokumen->file_path);
        $this->assertNull($dokumen->nomor_surat);
    }

    public function test_dokumen_pengaduan_langsung_berstatus_menunggu(): void
    {
        $this->post(route('dokumen.store'), $this->data('pengaduan_langsung', ['judul' => 'Aduan']))->assertSessionHasNoErrors();

        $this->assertSame(Dokumen::STATUS_MENUNGGU, Dokumen::firstOrFail()->status);
    }

    public function test_judul_wajib_diisi(): void
    {
        $this->post(route('dokumen.store'), $this->data('perizinan', ['judul' => '']))
            ->assertSessionHasErrors(['judul' => 'Judul wajib diisi.']);

        $this->assertDatabaseCount('dokumen', 0);
    }

    public function test_format_berkas_selain_pdf_jpg_png_ditolak(): void
    {
        $docx = UploadedFile::fake()->create('dokumen.docx', 10, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document');

        $this->post(route('dokumen.store'), $this->data('perizinan', ['berkas' => $docx]))
            ->assertSessionHasErrors(['berkas' => 'Berkas harus berformat PDF, JPG, atau PNG.']);

        $this->assertDatabaseCount('dokumen', 0);
    }

    public function test_nama_berkas_asli_dari_pengguna_tidak_dipakai_saat_penyimpanan(): void
    {
        $this->post(route('dokumen.store'), $this->data('perizinan', ['berkas' => $this->pdf('..-shell-<script>.pdf')]))->assertSessionHasNoErrors();

        $path = Dokumen::firstOrFail()->file_path;
        $this->assertMatchesRegularExpression('#^dokumen/\d{4}/\d{2}/[0-9a-f-]{36}\.pdf$#', $path);
        $this->assertStringNotContainsString('shell', $path);
    }

    public function test_berkas_lebih_dari_10_mb_ditolak(): void
    {
        $this->post(route('dokumen.store'), $this->data('perizinan', ['berkas' => $this->pdf('besar.pdf', 11000)]))
            ->assertSessionHasErrors(['berkas' => 'Ukuran berkas maksimal 10 MB.']);

        $this->assertDatabaseCount('dokumen', 0);
    }

    public function test_layanan_milik_admin_lain_ditolak_pada_formulir(): void
    {
        $this->post(route('dokumen.store'), $this->data('rekap_pengaduan_sp4n'))->assertSessionHasErrors('layanan_id');

        $this->assertDatabaseCount('dokumen', 0);
    }

    public function test_ubah_data_tanpa_mengganti_berkas(): void
    {
        $dokumen = $this->dokumenBerberkas();

        $this->put(route('dokumen.update', $dokumen), $this->data('perizinan', ['judul' => 'Judul Baru']))
            ->assertRedirect(route('dokumen.show', $dokumen));

        $dokumen->refresh();
        $this->assertSame('Judul Baru', $dokumen->judul);
        Storage::disk('local')->assertExists($dokumen->file_path);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'dokumen.ubah', 'dokumen_id' => $dokumen->id]);
    }

    public function test_ganti_berkas_menghapus_berkas_lama(): void
    {
        $dokumen = $this->dokumenBerberkas();
        $lama = $dokumen->file_path;

        $this->put(route('dokumen.update', $dokumen), $this->data('perizinan', ['berkas' => $this->pdf('baru.pdf')]))
            ->assertSessionHasNoErrors();

        $dokumen->refresh();
        $this->assertNotSame($lama, $dokumen->file_path);
        Storage::disk('local')->assertMissing($lama);
        Storage::disk('local')->assertExists($dokumen->file_path);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'dokumen.ganti_berkas', 'dokumen_id' => $dokumen->id]);
    }

    public function test_pindah_layanan_menyesuaikan_status(): void
    {
        $dokumen = $this->buatDokumen($this->pp, 'konsultasi');
        $this->assertNull($dokumen->status);

        $this->put(route('dokumen.update', $dokumen), $this->data('pengaduan_langsung'))->assertSessionHasNoErrors();
        $this->assertSame(Dokumen::STATUS_MENUNGGU, $dokumen->fresh()->status);

        $this->put(route('dokumen.update', $dokumen), $this->data('perizinan'))->assertSessionHasNoErrors();
        $this->assertNull($dokumen->fresh()->status);
    }

    public function test_hapus_dokumen_bersifat_soft_delete_dan_berkas_tetap_tersimpan(): void
    {
        $dokumen = $this->dokumenBerberkas();

        $this->delete(route('dokumen.destroy', $dokumen))->assertRedirect(route('dokumen.index'));

        $this->assertSoftDeleted('dokumen', ['id' => $dokumen->id]);
        Storage::disk('local')->assertExists($dokumen->file_path);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'dokumen.hapus', 'dokumen_id' => $dokumen->id]);
        $this->get(route('dokumen.show', $dokumen->id))->assertNotFound();
    }

    public function test_buka_dan_unduh_berkas_tercatat_di_log(): void
    {
        $dokumen = $this->dokumenBerberkas();

        $this->get(route('dokumen.berkas', $dokumen))->assertOk();
        $this->assertDatabaseMissing('log_aktivitas', ['aksi' => 'dokumen.unduh']);

        $this->get(route('dokumen.berkas', [$dokumen, 'unduh' => 1]))->assertOk()->assertDownload();
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'dokumen.unduh', 'dokumen_id' => $dokumen->id]);
    }

    public function test_dokumen_tanpa_berkas_menghasilkan_404_saat_dibuka(): void
    {
        $this->get(route('dokumen.berkas', $this->buatDokumen($this->pp)))->assertNotFound();
    }

    public function test_daftar_hanya_memuat_dokumen_layanan_milik_admin(): void
    {
        $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Milik PP']);
        $this->buatDokumen($this->buatUser('admin_sp4n'), 'rekap_pengaduan_sp4n', ['judul' => 'Milik SP4N']);

        $this->get('/dokumen')->assertOk()->assertSee('Milik PP')->assertDontSee('Milik SP4N');
    }

    public function test_daftar_dibagi_per_halaman(): void
    {
        foreach (range(1, 16) as $i) {
            $this->buatDokumen($this->pp, 'perizinan', ['judul' => "Dokumen {$i}"]);
        }

        $halaman1 = $this->get('/dokumen');
        $this->assertCount(15, $halaman1->viewData('dokumen'));

        $halaman2 = $this->get('/dokumen?page=2');
        $this->assertCount(1, $halaman2->viewData('dokumen'));
        $this->assertSame(16, $halaman2->viewData('dokumen')->total());
    }

    public function test_halaman_detail_menampilkan_data_dokumen(): void
    {
        $dokumen = $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Judul Detail', 'nomor_surat' => 'NS-77']);

        $this->get(route('dokumen.show', $dokumen))->assertOk()->assertSee('Judul Detail')->assertSee('NS-77')->assertSee('Tidak berlaku');
    }
}
