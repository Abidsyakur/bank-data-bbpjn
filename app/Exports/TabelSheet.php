<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/** Satu lembar Excel sederhana: judul, baris judul kolom, dan data. */
class TabelSheet implements FromArray, WithHeadings, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(
        private string $judul,
        private array $kolom,
        private array $baris,
    ) {
    }

    public function array(): array
    {
        return $this->baris;
    }

    public function headings(): array
    {
        return $this->kolom;
    }

    public function title(): string
    {
        return $this->judul;
    }

    public function styles(Worksheet $sheet): array
    {
        return [1 => ['font' => ['bold' => true]]];
    }
}
