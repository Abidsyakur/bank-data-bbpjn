<?php

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;
use ZipArchive;

/**
 * Backup = satu berkas ZIP berisi:
 *   manifest.json      ringkasan, daftar kolom, dan checksum
 *   database.ndjson    isi tabel, satu baris JSON per baris data (BUKAN SQL)
 *   berkas/...         seluruh berkas arsip (dokumen dan bukti tindak lanjut)
 *
 * Restore hanya memasukkan data ke tabel yang dikenal (TABEL), lewat query builder,
 * di dalam satu transaksi, sehingga berkas backup tidak dapat menjalankan perintah SQL sembarang.
 *
 * Jika kelak ada tabel baru, tambahkan namanya ke TABEL.
 */
class BackupService
{
    public const TABEL = ['roles', 'users', 'layanan', 'dokumen', 'tindak_lanjut', 'log_aktivitas', 'backup'];

    public const POLA_NAMA = '[A-Za-z0-9\-]+\.zip';

    private const FOLDER_BERKAS = ['dokumen', 'tindak-lanjut'];

    private const DIR = 'backup';

    /** Catatan folder berkas yang sedang ditukar saat restore, untuk pembatalan. */
    private array $tukar = [];

    public function disk(): FilesystemAdapter
    {
        return Storage::disk('local');
    }

    public function daftar(): Collection
    {
        $disk = $this->disk();

        return collect($disk->files(self::DIR))
            ->filter(fn ($p) => str_ends_with($p, '.zip'))
            ->map(function ($p) use ($disk) {
                $nama = basename($p);

                return [
                    'nama'   => $nama,
                    'ukuran' => $disk->size($p),
                    'waktu'  => Carbon::createFromTimestamp($disk->lastModified($p), config('app.timezone')),
                    'jenis'  => match (true) {
                        str_starts_with($nama, 'pra-restore') => 'Otomatis (sebelum restore)',
                        str_starts_with($nama, 'unggahan')    => 'Unggahan',
                        default                               => 'Manual',
                    },
                ];
            })
            ->sortByDesc('waktu')
            ->values();
    }

    public function ada(string $nama): bool
    {
        return (bool) preg_match('/^' . self::POLA_NAMA . '$/', $nama)
            && $this->disk()->exists(self::DIR . '/' . $nama);
    }

    public function jalur(string $nama): string
    {
        abort_unless($this->ada($nama), 404);

        return $this->disk()->path(self::DIR . '/' . $nama);
    }

    public function hapus(string $nama): void
    {
        if ($this->ada($nama)) {
            $this->disk()->delete(self::DIR . '/' . $nama);
        }
    }

    public function simpanUnggahan(UploadedFile $file): string
    {
        $nama = 'unggahan-' . now()->format('Ymd-His') . '.zip';
        $file->storeAs(self::DIR, $nama, 'local');

        return $nama;
    }

    /** Membuat backup baru dan mengembalikan nama berkasnya. */
    public function buat(string $awalan, ?User $oleh = null): string
    {
        @set_time_limit(0);

        $disk = $this->disk();
        $disk->makeDirectory(self::DIR . '/tmp');

        $nama = $awalan . '-' . now()->format('Ymd-His') . '.zip';
        $tmp  = $disk->path(self::DIR . '/tmp/db-' . Str::random(10) . '.ndjson');

        try {
            $h = fopen($tmp, 'wb');
            if ($h === false) {
                throw new RuntimeException('Tidak dapat menulis berkas sementara backup.');
            }

            $tabel = [];
            foreach (self::TABEL as $t) {
                $jumlah = 0;
                DB::table($t)->orderBy('id')->chunk(500, function ($rows) use ($h, $t, &$jumlah) {
                    foreach ($rows as $row) {
                        fwrite($h, json_encode(
                            ['t' => $t, 'r' => (array) $row],
                            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR
                        ) . "\n");
                        $jumlah++;
                    }
                });
                $tabel[$t] = ['kolom' => Schema::getColumnListing($t), 'jumlah' => $jumlah];
            }
            fclose($h);

            $berkas = [];
            $ukuran = 0;
            foreach (self::FOLDER_BERKAS as $folder) {
                foreach ($disk->allFiles($folder) as $p) {
                    $berkas[] = $p;
                    $ukuran  += $disk->size($p);
                }
            }

            $manifest = [
                'aplikasi'        => 'bank-data-bbpjn',
                'versi'           => 1,
                'dibuat'          => now()->toIso8601String(),
                'dibuat_oleh'     => $oleh?->nama,
                'tabel'           => $tabel,
                'berkas'          => ['jumlah' => count($berkas), 'ukuran' => $ukuran],
                'sha256_database' => hash_file('sha256', $tmp),
            ];

            $zip = new ZipArchive();
            if ($zip->open($disk->path(self::DIR . '/' . $nama), ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('Tidak dapat membuat berkas backup.');
            }
            $zip->addFromString('manifest.json', json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $zip->addFile($tmp, 'database.ndjson');
            foreach ($berkas as $p) {
                $zip->addFile($disk->path($p), 'berkas/' . $p);
            }
            if (! $zip->close()) {
                throw new RuntimeException('Gagal menyelesaikan berkas backup. Periksa ruang penyimpanan.');
            }
        } catch (Throwable $e) {
            $disk->delete(self::DIR . '/' . $nama);
            throw $e;
        } finally {
            if (is_file($tmp)) {
                @unlink($tmp);
            }
        }

        return $nama;
    }

    /** Memeriksa berkas backup tanpa mengubah apa pun. Mengembalikan manifest. */
    public function periksa(string $nama): array
    {
        $zip = new ZipArchive();
        if ($zip->open($this->jalur($nama)) !== true) {
            throw new RuntimeException('Berkas bukan arsip ZIP yang valid.');
        }

        try {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (! $this->namaAman($zip->getNameIndex($i))) {
                    throw new RuntimeException('Berkas backup berisi jalur yang tidak diizinkan.');
                }
            }

            $manifest = json_decode((string) $zip->getFromName('manifest.json'), true);
            if (! is_array($manifest) || ($manifest['aplikasi'] ?? null) !== 'bank-data-bbpjn') {
                throw new RuntimeException('Berkas ini bukan backup Bank Data Terpusat BBPJN.');
            }
            if (($manifest['versi'] ?? null) !== 1 || $zip->locateName('database.ndjson') === false) {
                throw new RuntimeException('Versi atau isi backup tidak dikenali.');
            }

            $tabel = $manifest['tabel'] ?? [];
            if (array_diff(self::TABEL, array_keys($tabel)) || array_diff(array_keys($tabel), self::TABEL)) {
                throw new RuntimeException('Daftar tabel pada backup tidak sesuai dengan aplikasi ini.');
            }

            foreach (self::TABEL as $t) {
                $kolomBackup = array_values($tabel[$t]['kolom'] ?? []);
                $kolomSaatIni = Schema::getColumnListing($t);
                sort($kolomBackup);
                sort($kolomSaatIni);
                if ($kolomBackup !== $kolomSaatIni) {
                    throw new RuntimeException("Struktur tabel {$t} pada backup berbeda dengan aplikasi saat ini.");
                }
            }

            return $manifest;
        } finally {
            $zip->close();
        }
    }

    /**
     * Memulihkan data dari backup. Mengembalikan nama backup pengaman yang dibuat otomatis.
     * Urutan: periksa -> ekstrak & cek checksum -> backup pengaman -> ganti data & berkas -> commit.
     */
    public function pulihkan(string $nama, User $oleh): string
    {
        @set_time_limit(0);

        $manifest = $this->periksa($nama);
        $disk = $this->disk();
        $kerja = $disk->path(self::DIR . '/tmp/restore-' . now()->format('Ymd-His') . '-' . Str::random(6));
        $lama = $kerja . '/lama';
        File::ensureDirectoryExists($kerja);

        try {
            $zip = new ZipArchive();
            if ($zip->open($this->jalur($nama)) !== true || ! $zip->extractTo($kerja)) {
                throw new RuntimeException('Gagal membuka isi berkas backup.');
            }
            $zip->close();

            $ndjson = $kerja . '/database.ndjson';
            if (! is_file($ndjson) || ! hash_equals((string) ($manifest['sha256_database'] ?? ''), hash_file('sha256', $ndjson))) {
                throw new RuntimeException('Data pada backup rusak (checksum tidak cocok).');
            }

            // Jaring pengaman: simpan kondisi saat ini sebelum diganti.
            $pra = $this->buat('pra-restore', $oleh);

            $this->tukar = [];
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            DB::beginTransaction();
            try {
                foreach (self::TABEL as $t) {
                    DB::table($t)->delete();
                }

                $this->isiDariNdjson($ndjson, $manifest);

                foreach (self::TABEL as $t) {
                    if (DB::table($t)->count() !== (int) $manifest['tabel'][$t]['jumlah']) {
                        throw new RuntimeException("Jumlah data tabel {$t} tidak cocok setelah pemulihan.");
                    }
                }

                $this->tukarBerkas($kerja, $lama);
                DB::commit();
            } catch (Throwable $e) {
                DB::rollBack();
                $this->kembalikanBerkas($lama);
                throw $e;
            } finally {
                DB::statement('SET FOREIGN_KEY_CHECKS=1');
            }

            return $pra;
        } finally {
            File::deleteDirectory($kerja);
        }
    }

    private function isiDariNdjson(string $ndjson, array $manifest): void
    {
        $h = fopen($ndjson, 'rb');
        if ($h === false) {
            throw new RuntimeException('Tidak dapat membaca data backup.');
        }

        $buf = [];
        $simpan = function (string $t) use (&$buf) {
            if (! empty($buf[$t])) {
                DB::table($t)->insert($buf[$t]);
                $buf[$t] = [];
            }
        };

        try {
            while (($baris = fgets($h)) !== false) {
                $baris = trim($baris);
                if ($baris === '') {
                    continue;
                }

                $d = json_decode($baris, true, 512, JSON_THROW_ON_ERROR);
                $t = $d['t'] ?? null;
                $r = $d['r'] ?? null;

                if (! in_array($t, self::TABEL, true) || ! is_array($r)
                    || array_diff(array_keys($r), $manifest['tabel'][$t]['kolom'])) {
                    throw new RuntimeException('Data backup tidak valid.');
                }

                $buf[$t][] = $r;
                if (count($buf[$t]) >= 500) {
                    $simpan($t);
                }
            }
        } finally {
            fclose($h);
        }

        foreach (self::TABEL as $t) {
            $simpan($t);
        }
    }

    /** Memindahkan folder berkas lama ke samping, lalu memasang folder berkas dari backup. */
    private function tukarBerkas(string $kerja, string $lama): void
    {
        File::ensureDirectoryExists($lama);

        foreach (self::FOLDER_BERKAS as $f) {
            $sekarang = $this->disk()->path($f);
            $baru = $kerja . '/berkas/' . $f;
            $this->tukar[$f] = ['lama' => false, 'baru' => false];

            if (is_dir($sekarang)) {
                if (! rename($sekarang, $lama . '/' . $f)) {
                    throw new RuntimeException('Gagal memindahkan berkas arsip yang lama.');
                }
                $this->tukar[$f]['lama'] = true;
            }

            if (is_dir($baru)) {
                if (! rename($baru, $sekarang)) {
                    throw new RuntimeException('Gagal memasang berkas arsip dari backup.');
                }
                $this->tukar[$f]['baru'] = true;
            }
        }
    }

    private function kembalikanBerkas(string $lama): void
    {
        foreach ($this->tukar as $f => $status) {
            $sekarang = $this->disk()->path($f);

            if ($status['baru'] && is_dir($sekarang)) {
                File::deleteDirectory($sekarang);
            }
            if ($status['lama'] && is_dir($lama . '/' . $f)) {
                rename($lama . '/' . $f, $sekarang);
            }
        }

        $this->tukar = [];
    }

    private function namaAman(string|false $n): bool
    {
        if ($n === false || $n === '') {
            return false;
        }
        if (str_contains($n, '..') || str_contains($n, '\\') || str_contains($n, ':') || str_starts_with($n, '/')) {
            return false;
        }

        return $n === 'manifest.json'
            || $n === 'database.ndjson'
            || $n === 'berkas/'
            || str_starts_with($n, 'berkas/dokumen/')
            || str_starts_with($n, 'berkas/tindak-lanjut/');
    }
}
