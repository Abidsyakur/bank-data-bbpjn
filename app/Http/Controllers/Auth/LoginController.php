<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Support\PesanValidasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $ok = Auth::attempt(
            [
                'username' => $credentials['username'],
                'password' => $credentials['password'],
                'aktif'    => true,
            ],
            $request->boolean('remember')
        );

        if (! $ok) {
            return back()
                ->withErrors(['username' => 'Username atau kata sandi salah, atau akun tidak aktif.'])
                ->onlyInput('username');
        }

        $request->session()->regenerate();
        ActivityLogger::catat('login');

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request): RedirectResponse
    {
        ActivityLogger::catat('logout');

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
