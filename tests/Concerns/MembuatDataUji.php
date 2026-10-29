<?php

namespace Tests\Concerns;

use App\Models\Dokumen;
use App\Models\Layanan;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\LayananSeeder;
use Database\Seeders\RolesSeeder;

/** Pembantu untuk menyiapkan data uji yang sama dengan data bawaan aplikasi. */
trait MembuatDataUji
{
    protected function siapkanDataDasar(): void
    {
        $this->seed([RolesSeeder::class, LayananSeeder::class]);
    }

    protected function buatUser(string $roleKode = 'admin_pp', ?string $username = null, bool $aktif = true): User
    {
        $role = Role::where('kode', $roleKode)->firstOrFail();

        return User::create([
            'role_id'  => $role->id,
            'nama'     => 'Uji ' . $roleKode,
            'username' => $username ?? $roleKode,
            'password' => 'password123',
            'aktif'    => $aktif,
        ]);
    }

    protected function layanan(string $kode): Layanan
    {
        return Layanan::where('kode', $kode)->firstOrFail();
    }

    protected function buatDokumen(User $oleh, string $kodeLayanan = 'perizinan', array $atribut = []): Dokumen
    {
        $layanan = $this->layanan($kodeLayanan);

        return Dokumen::create(array_merge([
            'user_id'     => $oleh->id,
            'layanan_id'  => $layanan->id,
            'nomor_surat' => null,
            'judul'       => 'Dokumen uji',
            'tanggal'     => now()->toDateString(),
            'file_path'   => null,
            'status'      => $layanan->butuh_tindak_lanjut ? Dokumen::STATUS_MENUNGGU : null,
        ], $atribut));
    }
}
