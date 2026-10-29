<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Data besar untuk uji beban (k6) dan uji performa. HANYA untuk komputer pengembangan.
 *
 *   php artisan db:seed --class=PerformaSeeder          (bawaan 20.000 dokumen)
 *   set PERFORMA_JUMLAH=50000 && php artisan db:seed --class=PerformaSeeder
 *
 * Pembersihan:
 *   DELETE FROM dokumen WHERE judul LIKE '[PERFORMA]%';
 *   DELETE FROM log_aktivitas WHERE keterangan LIKE '[PERFORMA]%';
 */
class PerformaSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('PerformaSeeder dilarang dijalankan di lingkungan production.');
        }

        $jumlah = (int) (getenv('PERFORMA_JUMLAH') ?: 20000);
        $layanan = DB::table('layanan')->get();
        $pengguna = DB::table('users')->pluck('id', 'role_id');

        if ($layanan->isEmpty() || $pengguna->isEmpty()) {
            throw new RuntimeException('Jalankan db:seed biasa dulu agar layanan dan akun tersedia.');
        }

        $dokumen = [];
        $log = [];

        for ($i = 0; $i < $jumlah; $i++) {
            $l = $layanan[$i % $layanan->count()];
            $userId = $pengguna[$l->role_id] ?? $pengguna->first();
            $tanggal = now()->subDays(random_int(0, 400));

            $dokumen[] = [
                'user_id'     => $userId,
                'layanan_id'  => $l->id,
                'nomor_surat' => sprintf('%05d/PERF/%d', $i, $tanggal->year),
                'judul'       => "[PERFORMA] Dokumen contoh {$i}",
                'tanggal'     => $tanggal->toDateString(),
                'file_path'   => null,
                'status'      => $l->butuh_tindak_lanjut ? (random_int(0, 9) < 6 ? 'selesai' : 'menunggu') : null,
                'created_at'  => $tanggal,
                'updated_at'  => $tanggal,
            ];

            $log[] = [
                'user_id'    => $userId,
                'dokumen_id' => null,
                'aksi'       => 'dokumen.tambah',
                'keterangan' => "[PERFORMA] contoh {$i}",
                'waktu'      => $tanggal,
            ];

            if (count($dokumen) === 1000) {
                DB::table('dokumen')->insert($dokumen);
                DB::table('log_aktivitas')->insert($log);
                $dokumen = $log = [];
            }
        }

        if ($dokumen) {
            DB::table('dokumen')->insert($dokumen);
            DB::table('log_aktivitas')->insert($log);
        }

        $this->command?->info("{$jumlah} dokumen dan {$jumlah} baris log contoh dibuat.");
    }
}
