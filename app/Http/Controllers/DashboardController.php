<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user()->loadMissing('role');

        // Sementara: tampilkan layanan yang menjadi tanggung jawab pengguna.
        // Rekapitulasi dokumen dibuat setelah modul arsip selesai.
        $layanan = DB::table('layanan')
            ->when(! $user->isPimpinan(), fn ($q) => $q->where('role_id', $user->role_id))
            ->orderBy('nama')
            ->get();

        return view('dashboard', compact('user', 'layanan'));
    }
}
