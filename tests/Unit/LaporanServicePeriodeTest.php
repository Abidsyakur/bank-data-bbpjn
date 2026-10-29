<?php

namespace Tests\Unit;

use App\Services\LaporanService;
use Tests\TestCase;

class LaporanServicePeriodeTest extends TestCase
{
    public function test_periode_lengkap(): void
    {
        $this->assertSame(
            '01/10/2026 - 27/10/2026',
            (new LaporanService())->periode(['dari' => '2026-10-01', 'sampai' => '2026-10-27', 'layanan' => null])
        );
    }

    public function test_periode_hanya_tanggal_mulai(): void
    {
        $this->assertSame('Sejak 01/10/2026', (new LaporanService())->periode(['dari' => '2026-10-01', 'sampai' => null, 'layanan' => null]));
    }

    public function test_periode_hanya_tanggal_akhir(): void
    {
        $this->assertSame('Sampai 27/10/2026', (new LaporanService())->periode(['dari' => null, 'sampai' => '2026-10-27', 'layanan' => null]));
    }

    public function test_periode_tanpa_batas_tanggal(): void
    {
        $this->assertSame('Semua tanggal', (new LaporanService())->periode(['dari' => null, 'sampai' => null, 'layanan' => null]));
    }
}
