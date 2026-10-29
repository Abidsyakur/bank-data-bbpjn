<?php

namespace Tests\Unit;

use App\Support\Format;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class FormatTest extends TestCase
{
    public static function kasus(): array
    {
        return [
            'nol'              => [0, '0 B'],
            'byte'             => [512, '512 B'],
            'tepat 1 KB'       => [1024, '1,0 KB'],
            'pecahan KB'       => [1536, '1,5 KB'],
            'batas 10 MB'      => [10 * 1024 * 1024, '10,0 MB'],
            'ribuan MB'        => [1023 * 1024 * 1024, '1.023,0 MB'],
            'gigabyte'         => [1073741824, '1,0 GB'],
            'terabyte'         => [5 * 1024 ** 4, '5,0 TB'],
            'melebihi terabyte' => [2048 * 1024 ** 4, '2.048,0 TB'],
        ];
    }

    #[DataProvider('kasus')]
    public function test_ukuran_ditulis_dalam_satuan_yang_mudah_dibaca(int|float $bytes, string $diharapkan): void
    {
        $this->assertSame($diharapkan, Format::ukuran($bytes));
    }
}
