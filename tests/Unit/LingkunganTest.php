<?php

namespace Tests\Unit;

use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Diagnosis lingkungan: tes ini gagal dengan pesan yang jelas bila PHP atau paket belum siap,
 * sehingga penyebab kegagalan tes lain (PDF, Excel, gambar) cepat ditemukan.
 */
class LingkunganTest extends TestCase
{
    public static function ekstensiWajib(): array
    {
        return [
            'gd (PDF, Excel, tes gambar)' => ['gd'],
            'zip (backup dan Excel)' => ['zip'],
            'fileinfo (validasi berkas)' => ['fileinfo'],
            'mbstring' => ['mbstring'],
            'openssl' => ['openssl'],
            'pdo_sqlite (tes cepat)' => ['pdo_sqlite'],
            'pdo_mysql (aplikasi)' => ['pdo_mysql'],
        ];
    }

    #[DataProvider('ekstensiWajib')]
    public function test_ekstensi_php_aktif(string $ekstensi): void
    {
        $this->assertTrue(
            extension_loaded($ekstensi),
            "Ekstensi PHP '{$ekstensi}' belum aktif. Jalankan 'php --ini', buka php.ini yang dimuat, hapus tanda ; pada baris extension={$ekstensi}."
        );
    }

    public function test_versi_php_didukung_laravel_12(): void
    {
        $this->assertTrue(version_compare(PHP_VERSION, '8.2.0', '>='), 'Laravel 12 memerlukan PHP 8.2 atau lebih baru. Versi sekarang: '.PHP_VERSION);
    }

    public function test_paket_pdf_dan_excel_terpasang(): void
    {
        $perintah = 'Jalankan: composer require barryvdh/laravel-dompdf maatwebsite/excel';

        $this->assertTrue(class_exists(Pdf::class), "Paket laravel-dompdf belum terpasang. {$perintah}");
        $this->assertTrue(class_exists(Excel::class), "Paket maatwebsite/excel belum terpasang. {$perintah}");
    }

    /**
     * PHP2Shell (CVE-2026-109618 dan CVE-2026-109619) diperbaiki pada PHP 8.5.12, 8.4.27, 8.3.36, dan 8.2.35.
     * Catatan: paket PHP distribusi Linux dapat menambal tanpa mengubah nomor versi; tes ini ditujukan untuk build resmi (XAMPP, setup-php).
     */
    public function test_versi_php_sudah_memuat_perbaikan_keamanan_terbaru(): void
    {
        $minimum = ['8.2' => '8.2.35', '8.3' => '8.3.36', '8.4' => '8.4.27', '8.5' => '8.5.12'];
        $cabang = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

        if (! isset($minimum[$cabang])) {
            $this->assertTrue(true);

            return;
        }

        $this->assertTrue(
            version_compare(PHP_VERSION, $minimum[$cabang], '>='),
            'PHP '.PHP_VERSION." belum memuat perbaikan PHP2Shell (minimum {$minimum[$cabang]}). Perbarui PHP sebelum sistem dipakai di jaringan."
        );
    }
}
