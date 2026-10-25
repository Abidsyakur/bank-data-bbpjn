<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Models\Layanan;
use App\Services\ActivityLogger;
use App\Services\LaporanService;
use App\Support\PesanValidasi;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/** Khusus Pimpinan (dijaga middleware role:pimpinan di routes). */
class LaporanController extends Controller
{
    public function __construct(private LaporanService $laporanService)
    {
    }

    public function index(Request $request): View
    {
        $filter = $this->filter($request);

        return view('laporan.index', [
            'laporan'     => $this->laporanService->bangun($filter),
            'filter'      => $filter,
            'layananList' => Layanan::orderBy('nama')->get(),
        ]);
    }

    public function pdf(Request $request): Response
    {
        $filter = $this->filter($request);
        $laporan = $this->laporanService->bangun($filter);

        ActivityLogger::catat('laporan.pdf', $laporan['periode'] . ' | ' . $laporan['kategori']);

        return Pdf::loadView('laporan.pdf', $laporan + [
            'dicetakOleh' => $request->user()->nama,
            'dicetakPada' => now(),
        ])->setPaper('a4', 'landscape')->download($this->namaBerkas('pdf'));
    }

    public function excel(Request $request): BinaryFileResponse
    {
        $filter = $this->filter($request);
        $laporan = $this->laporanService->bangun($filter);

        ActivityLogger::catat('laporan.excel', $laporan['periode'] . ' | ' . $laporan['kategori']);

        return Excel::download(new LaporanExport($laporan), $this->namaBerkas('xlsx'));
    }

    private function filter(Request $request): array
    {
        $data = $request->validate([
            'dari'    => ['nullable', 'date'],
            'sampai'  => ['nullable', 'date', 'after_or_equal:dari'],
            'layanan' => ['nullable', Rule::exists('layanan', 'kode')],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        // Kunjungan pertama (tanpa parameter): bulan berjalan. Kolom dikosongkan sengaja = semua tanggal.
        return [
            'dari'    => $request->has('dari') ? ($data['dari'] ?? null) : now()->startOfMonth()->toDateString(),
            'sampai'  => $request->has('sampai') ? ($data['sampai'] ?? null) : now()->toDateString(),
            'layanan' => $data['layanan'] ?? null,
        ];
    }

    private function namaBerkas(string $ekstensi): string
    {
        return 'laporan-rekapitulasi-' . now()->format('Ymd-His') . '.' . $ekstensi;
    }
}
