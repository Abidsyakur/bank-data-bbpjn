<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\PesanValidasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PasswordController extends Controller
{
    public function edit(): View
    {
        return view('akun.ganti-sandi');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'string', 'min:8', 'confirmed'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $request->user()->update(['password' => $data['password']]);
        ActivityLogger::catat('ganti_sandi');

        return redirect()->route('sandi.edit')->with('status', 'Kata sandi berhasil diubah.');
    }
}
