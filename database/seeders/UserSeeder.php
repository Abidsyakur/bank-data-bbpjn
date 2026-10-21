<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Akun contoh untuk pengembangan lokal.
     * WAJIB diganti/dihapus sebelum dipakai di server sebenarnya.
     */
    public function run(): void
    {
        $roleId = DB::table('roles')->pluck('id', 'kode');

        $users = [
            ['pimpinan',   'Pimpinan',                'pimpinan'],
            ['admin_pp',   'Admin Pelayanan Publik',  'admin_pp'],
            ['admin_sp4n', 'Admin SP4N-LAPOR!',       'admin_sp4n'],
            ['admin_jaki', 'Admin JAKI',              'admin_jaki'],
        ];

        foreach ($users as [$username, $nama, $role]) {
            DB::table('users')->updateOrInsert(
                ['username' => $username],
                [
                    'role_id'    => $roleId[$role],
                    'nama'       => $nama,
                    'password'   => Hash::make('password'),
                    'aktif'      => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }
}
