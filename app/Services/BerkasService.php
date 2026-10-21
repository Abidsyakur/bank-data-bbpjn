<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Satu-satunya tempat yang menyentuh berkas fisik.
 * Berkas disimpan di storage privat (storage/app/private), bukan folder publik.
 */
class BerkasService
{
    private const DISK = 'local';

    public function simpan(UploadedFile $file, string $folder = 'dokumen'): string
    {
        $folder = $folder . '/' . now()->format('Y/m');
        $nama   = Str::uuid() . '.' . $file->extension();

        return $file->storeAs($folder, $nama, self::DISK);
    }

    public function hapus(?string $path): void
    {
        if ($path) {
            Storage::disk(self::DISK)->delete($path);
        }
    }

    public function ada(?string $path): bool
    {
        return $path !== null && Storage::disk(self::DISK)->exists($path);
    }

    public function tampilkan(string $path, string $nama): StreamedResponse
    {
        return Storage::disk(self::DISK)->response($path, $nama);
    }

    public function unduh(string $path, string $nama): StreamedResponse
    {
        return Storage::disk(self::DISK)->download($path, $nama);
    }
}
