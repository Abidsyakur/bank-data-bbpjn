<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Satu-satunya pintu untuk menulis ke tabel log_aktivitas.
 * Dipakai di semua modul: ActivityLogger::catat('login');
 */
class ActivityLogger
{
    public static function catat(
        string $aksi,
        ?string $keterangan = null,
        ?int $dokumenId = null,
        ?int $userId = null
    ): void {
        $userId ??= Auth::id();

        if ($userId === null) {
            return;
        }

        DB::table('log_aktivitas')->insert([
            'user_id'     => $userId,
            'dokumen_id'  => $dokumenId,
            'aksi'        => $aksi,
            'keterangan'  => $keterangan !== null ? mb_substr($keterangan, 0, 255) : null,
            'waktu'       => now(),
        ]);
    }
}
