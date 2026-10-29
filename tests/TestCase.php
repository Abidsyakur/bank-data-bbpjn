<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        $this->lindungiDatabaseNyata();

        parent::setUp();

        // Tampilan memakai @vite; tes tidak perlu server Vite atau hasil build.
        $this->withoutVite();
    }

    /**
     * Pengaman: RefreshDatabase menghapus seluruh tabel. Tes hanya boleh berjalan di SQLite
     * (memori) atau di database MySQL yang namanya berakhiran _test, bukan database aplikasi.
     */
    private function lindungiDatabaseNyata(): void
    {
        $koneksi = getenv('DB_CONNECTION') ?: ($_ENV['DB_CONNECTION'] ?? 'sqlite');
        $nama = (string) (getenv('DB_DATABASE') ?: ($_ENV['DB_DATABASE'] ?? ':memory:'));

        if ($koneksi !== 'sqlite' && ! str_ends_with($nama, '_test')) {
            throw new RuntimeException(
                "Tes dihentikan: database '{$nama}' bukan database uji. Gunakan nama berakhiran _test, misalnya bank_data_bbpjn_test."
            );
        }
    }
}
