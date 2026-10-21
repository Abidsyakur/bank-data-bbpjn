<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LayananSeeder extends Seeder
{
    public function run(): void
    {
        $roleId = DB::table('roles')->pluck('id', 'kode');

        // [kode, nama, role, is_pengaduan, butuh_tindak_lanjut]
        $layanan = [
            ['perizinan',              'Perizinan',                        'admin_pp',   false, false], // TODO: konfirmasi apakah perlu tindak lanjut
            ['konsultasi',             'Layanan Konsultasi',               'admin_pp',   false, false],
            ['pengaduan_langsung',     'Pengaduan Langsung',               'admin_pp',   true,  true],
            ['arsip_ppid',             'Arsip PPID',                       'admin_pp',   false, false],
            ['laporan_skm',            'Laporan SKM',                      'admin_pp',   false, false],
            ['rekap_pengaduan_sp4n',   'Rekap Pengaduan Online SP4N',      'admin_sp4n', true,  true],
            ['laporan_kinerja_sp4n',   'Laporan Kinerja SP4N',             'admin_sp4n', false, false],
            ['laporan_kerusakan_jaki', 'Laporan Kerusakan Jalan/Jembatan', 'admin_jaki', true,  true],
            ['laporan_kinerja_jaki',   'Laporan Kinerja JAKI',             'admin_jaki', false, false],
        ];

        foreach ($layanan as [$kode, $nama, $role, $isPengaduan, $butuhTl]) {
            DB::table('layanan')->updateOrInsert(
                ['kode' => $kode],
                [
                    'role_id'             => $roleId[$role],
                    'nama'                => $nama,
                    'is_pengaduan'        => $isPengaduan,
                    'butuh_tindak_lanjut' => $butuhTl,
                    'created_at'          => now(),
                    'updated_at'          => now(),
                ]
            );
        }
    }
}
