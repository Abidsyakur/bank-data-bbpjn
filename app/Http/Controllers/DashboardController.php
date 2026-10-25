<?php

namespace App\Http\Controllers;

use App\Models\Dokumen;
use App\Models\Layanan;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('role');

        // Rekap per layanan; admin hanya melihat layanan miliknya.
        $rekap = Layanan::untukUser($user)
            ->orderBy('nama')
            ->withCount([
                'dokumen as total',
                'dokumen as menunggu' => fn ($q) => $q->where('status', Dokumen::STATUS_MENUNGGU),
                'dokumen as selesai'  => fn ($q) => $q->where('status', Dokumen::STATUS_SELESAI),
            ])
            ->get();

        $pengaduan = $rekap->where('is_pengaduan', true);

        // Hanya layanan tertentu yang melacak status; sisanya murni arsip.
        $berstatus = $rekap->where('butuh_tindak_lanjut', true)->values();
        $arsip     = $rekap->where('butuh_tindak_lanjut', false)->values();

        // Persentase = selesai / (menunggu + selesai), hanya dari layanan yang melacak status.
        $dipantau = $berstatus->sum('menunggu') + $berstatus->sum('selesai');

        $ringkasan = [
            'total'              => $rekap->sum('total'),
            'menunggu'           => $berstatus->sum('menunggu'),
            'selesai'            => $berstatus->sum('selesai'),
            'pengaduan'          => $pengaduan->sum('total'),
            'pengaduan_menunggu' => $pengaduan->where('butuh_tindak_lanjut', true)->sum('menunggu'),
            'pengaduan_berstatus' => $pengaduan->where('butuh_tindak_lanjut', true)->isNotEmpty(),
            'ada_status'         => $berstatus->isNotEmpty(),
            'dipantau'           => $dipantau,
            'persen_selesai'     => $dipantau > 0 ? (int) round($berstatus->sum('selesai') / $dipantau * 100) : null,
            'arsip'              => $arsip->sum('total'),
        ];

        // Dokumen per bulan, 12 bulan terakhir (sintaks DATE_FORMAT khusus MySQL/MariaDB).
        $awal = now()->startOfMonth()->subMonths(11);
        $perBulan = Dokumen::dapatDilihat($user)
            ->where('tanggal', '>=', $awal->toDateString())
            ->selectRaw("DATE_FORMAT(tanggal, '%Y-%m') as bulan, COUNT(*) as jumlah")
            ->groupBy('bulan')
            ->pluck('jumlah', 'bulan');

        $labelBulan = [];
        $dataBulan = [];
        for ($i = 0; $i < 12; $i++) {
            $bulan = $awal->copy()->addMonths($i);
            $labelBulan[] = $bulan->locale('id')->translatedFormat('M Y');
            $dataBulan[]  = (int) ($perBulan[$bulan->format('Y-m')] ?? 0);
        }

        $chartBulan = [
            'type' => 'bar',
            'data' => [
                'labels'   => $labelBulan,
                'datasets' => [[
                    'label'           => 'Dokumen',
                    'data'            => $dataBulan,
                    'backgroundColor' => '#40517e',
                    'borderRadius'    => 4,
                ]],
            ],
            'options' => [
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => ['legend' => ['display' => false]],
                'scales'              => ['y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            ],
        ];

        // Grafik status: hanya layanan yang melacak tindak lanjut (tanpa kelompok "Tanpa status").
        $chartStatus = [
            'type' => 'bar',
            'data' => [
                'labels'   => $berstatus->pluck('nama')->all(),
                'datasets' => [
                    ['label' => 'Menunggu', 'data' => $berstatus->pluck('menunggu')->all(), 'backgroundColor' => '#d93a4a'],
                    ['label' => 'Selesai', 'data' => $berstatus->pluck('selesai')->all(), 'backgroundColor' => '#1f7a4d'],
                ],
            ],
            'options' => [
                'indexAxis'           => 'y',
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'scales'              => [
                    'x' => ['stacked' => true, 'beginAtZero' => true, 'ticks' => ['precision' => 0]],
                    'y' => ['stacked' => true],
                ],
            ],
        ];

        // Grafik arsip: jumlah dokumen saja, tanpa status.
        $chartArsip = [
            'type' => 'bar',
            'data' => [
                'labels'   => $arsip->pluck('nama')->all(),
                'datasets' => [[
                    'label'           => 'Dokumen',
                    'data'            => $arsip->pluck('total')->all(),
                    'backgroundColor' => '#40517e',
                    'borderRadius'    => 4,
                ]],
            ],
            'options' => [
                'indexAxis'           => 'y',
                'responsive'          => true,
                'maintainAspectRatio' => false,
                'plugins'             => ['legend' => ['display' => false]],
                'scales'              => ['x' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]]],
            ],
        ];

        $tinggiStatus = max(160, 44 * $berstatus->count() + 70);
        $tinggiArsip  = max(160, 44 * $arsip->count() + 50);

        return view('dashboard', compact('user', 'berstatus', 'arsip', 'ringkasan', 'chartBulan', 'chartStatus', 'chartArsip', 'tinggiStatus', 'tinggiArsip'));
    }
}
