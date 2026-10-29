<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

/**
 * Uji performa tingkat aplikasi: memastikan jumlah query tidak membesar mengikuti jumlah data (tanpa N+1)
 * dan halaman utama tetap cepat pada data besar. Dijalankan hanya bila diminta:
 *   set RUN_PERFORMANCE_TESTS=1
 *   php artisan test --filter=PerformaTest
 */
class PerformaTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    private User $pimpinan;

    protected function setUp(): void
    {
        parent::setUp();

        if (! getenv('RUN_PERFORMANCE_TESTS')) {
            $this->markTestSkipped('Atur RUN_PERFORMANCE_TESTS=1 untuk menjalankan uji performa.');
        }

        $this->siapkanDataDasar();
        $this->pimpinan = $this->buatUser('pimpinan');
        $this->actingAs($this->pimpinan);
    }

    private function isiMassal(int $jumlah): void
    {
        $pp = $this->buatUser('admin_pp', 'pp_massal_' . random_int(1, 999999));
        $layanan = DB::table('layanan')->get();
        $baris = [];
        $log = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $l = $layanan[$i % $layanan->count()];
            $baris[] = [
                'user_id' => $pp->id, 'layanan_id' => $l->id, 'nomor_surat' => 'NS-' . random_int(1, 99999),
                'judul' => 'Dokumen massal ' . $i, 'tanggal' => now()->subDays($i % 300)->toDateString(),
                'file_path' => null, 'status' => $l->butuh_tindak_lanjut ? ($i % 2 ? 'menunggu' : 'selesai') : null,
                'created_at' => now(), 'updated_at' => now(),
            ];
            $log[] = [
                'user_id' => $pp->id, 'dokumen_id' => null, 'aksi' => 'dokumen.tambah',
                'keterangan' => 'massal ' . $i, 'waktu' => now()->subMinutes($i),
            ];
        }

        foreach (array_chunk($baris, 500) as $bagian) {
            DB::table('dokumen')->insert($bagian);
        }
        foreach (array_chunk($log, 500) as $bagian) {
            DB::table('log_aktivitas')->insert($bagian);
        }
    }

    private function hitungQuery(string $alamat): int
    {
        $this->get($alamat)->assertOk(); // pemanasan

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->get($alamat)->assertOk();
        $jumlah = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $jumlah;
    }

    public function test_jumlah_query_halaman_utama_tidak_bertambah_saat_data_membesar(): void
    {
        $halaman = ['/dashboard', '/dokumen', '/dokumen?q=massal', '/dokumen?layanan=pengaduan_langsung&status=menunggu', '/riwayat', '/laporan?dari=&sampai='];

        $this->isiMassal(20);
        $sebelum = [];
        foreach ($halaman as $alamat) {
            $sebelum[$alamat] = $this->hitungQuery($alamat);
        }

        $this->isiMassal(3000);
        foreach ($halaman as $alamat) {
            $this->assertSame($sebelum[$alamat], $this->hitungQuery($alamat), "Jumlah query {$alamat} bertambah seiring data (indikasi N+1).");
        }
    }

    public function test_halaman_utama_tetap_cepat_pada_data_besar(): void
    {
        $this->isiMassal(5000);

        foreach (['/dashboard' => 3.0, '/dokumen' => 2.0, '/dokumen?q=massal' => 2.5, '/riwayat' => 2.0] as $alamat => $batas) {
            $this->get($alamat)->assertOk(); // pemanasan
            $mulai = microtime(true);
            $this->get($alamat)->assertOk();
            $detik = microtime(true) - $mulai;

            $this->assertLessThan($batas, $detik, sprintf('%s membutuhkan %.2f detik (batas %.1f).', $alamat, $detik, $batas));
        }
    }

    public function test_pencarian_nomor_surat_memanfaatkan_indeks(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Pemeriksaan rencana query ditulis untuk SQLite.');
        }

        $this->isiMassal(500);
        $rencana = collect(DB::select("EXPLAIN QUERY PLAN SELECT * FROM dokumen WHERE nomor_surat = 'NS-1'"));

        $this->assertTrue($rencana->contains(fn ($baris) => str_contains(strtolower($baris->detail), 'index')), 'Pencarian nomor surat tidak memakai indeks.');
    }
}
