<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            ['kode' => 'pimpinan',   'nama' => 'Pimpinan'],
            ['kode' => 'admin_pp',   'nama' => 'Admin Pelayanan Publik'],
            ['kode' => 'admin_sp4n', 'nama' => 'Admin SP4N-LAPOR!'],
            ['kode' => 'admin_jaki', 'nama' => 'Admin JAKI'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert(['kode' => $role['kode']], $role);
        }
    }
}
