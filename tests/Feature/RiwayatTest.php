<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class RiwayatTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    private User $pimpinan;
    private User $pp;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataDasar();
        $this->pimpinan = $this->buatUser('pimpinan');
        $this->pp = $this->buatUser('admin_pp');
    }

    private function logDi(string $aksi, string $waktu, ?int $userId = null, ?int $dokumenId = null): void
    {
        DB::table('log_aktivitas')->insert([
            'user_id' => $userId ?? $this->pp->id, 'dokumen_id' => $dokumenId, 'aksi' => $aksi,
            'keterangan' => "uji {$aksi}", 'waktu' => $waktu,
        ]);
    }

    public function test_pimpinan_melihat_aktivitas_dengan_label_yang_mudah_dibaca(): void
    {
        ActivityLogger::catat('login', null, null, $this->pp->id);
        ActivityLogger::catat('dokumen.hapus', 'Judul X', null, $this->pp->id);

        $this->actingAs($this->pimpinan)->get('/riwayat')
            ->assertOk()
            ->assertSee('Masuk (login)')
            ->assertSee('Hapus dokumen')
            ->assertSee('Judul X');
    }

    public function test_aktivitas_terbaru_berada_di_atas(): void
    {
        $this->logDi('login', '2026-10-01 08:00:00');
        $this->logDi('logout', '2026-10-01 17:00:00');

        $log = $this->actingAs($this->pimpinan)->get('/riwayat')->viewData('log');

        $this->assertSame('logout', $log->first()->aksi);
    }

    public function test_filter_pengguna(): void
    {
        ActivityLogger::catat('login', null, null, $this->pp->id);
        ActivityLogger::catat('login', null, null, $this->pimpinan->id);

        $log = $this->actingAs($this->pimpinan)->get('/riwayat?pengguna=' . $this->pp->id)->viewData('log');

        $this->assertCount(1, $log);
        $this->assertSame($this->pp->username, $log->first()->username);
    }

    public function test_filter_jenis_aktivitas(): void
    {
        $this->logDi('login', '2026-10-01 08:00:00');
        $this->logDi('dokumen.hapus', '2026-10-01 09:00:00');

        $log = $this->actingAs($this->pimpinan)->get('/riwayat?aksi=dokumen.hapus')->viewData('log');

        $this->assertCount(1, $log);
        $this->assertSame('dokumen.hapus', $log->first()->aksi);
    }

    public function test_filter_periode(): void
    {
        $this->logDi('login', '2026-09-01 10:00:00');
        $this->logDi('logout', '2026-10-05 10:00:00');

        $log = $this->actingAs($this->pimpinan)->get('/riwayat?dari=2026-10-01&sampai=2026-10-31')->viewData('log');

        $this->assertCount(1, $log);
        $this->assertSame('logout', $log->first()->aksi);
    }

    public function test_jenis_aktivitas_tidak_dikenal_dan_tanggal_terbalik_ditolak(): void
    {
        $this->actingAs($this->pimpinan);

        $this->get('/riwayat?aksi=ngawur')->assertSessionHasErrors('aksi');
        $this->get('/riwayat?dari=2026-10-10&sampai=2026-10-01')->assertSessionHasErrors('sampai');
    }

    public function test_dokumen_yang_dihapus_ditandai_tanpa_tautan(): void
    {
        $dokumen = $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Dokumen Hilang']);
        $aktif = $this->buatDokumen($this->pp, 'perizinan', ['judul' => 'Dokumen Aktif']);
        $this->logDi('dokumen.tambah', '2026-10-01 08:00:00', null, $dokumen->id);
        $this->logDi('dokumen.tambah', '2026-10-01 09:00:00', null, $aktif->id);
        $dokumen->delete();

        $this->actingAs($this->pimpinan)->get('/riwayat')
            ->assertSee('(telah dihapus)')
            ->assertSee(route('dokumen.show', $aktif))
            ->assertDontSee(route('dokumen.show', $dokumen));
    }

    public function test_daftar_dibagi_25_baris_per_halaman(): void
    {
        foreach (range(1, 30) as $i) {
            $this->logDi('login', sprintf('2026-10-01 08:%02d:00', $i));
        }

        $this->actingAs($this->pimpinan);

        $this->assertCount(25, $this->get('/riwayat')->viewData('log'));
        $this->assertCount(5, $this->get('/riwayat?page=2')->viewData('log'));
    }

    public function test_pesan_kosong_saat_tidak_ada_aktivitas_yang_cocok(): void
    {
        $this->actingAs($this->pimpinan)->get('/riwayat?aksi=backup.buat')->assertSee('Tidak ada aktivitas yang cocok dengan filter.');
    }
}
