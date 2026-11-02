<?php

namespace Database\Factories;

use App\Models\Dokumen;
use Illuminate\Database\Eloquent\Factories\Factory;

class DokumenFactory extends Factory
{
    protected $model = Dokumen::class;

    public function definition(): array
    {
        return [
            'judul' => $this->faker->sentence(),
            'nomor_surat' => $this->faker->unique()->numerify('SURAT-###/2026'),
            // Sesuaikan kolom lain yang wajib diisi di tabel Anda
        ];
    }
}
