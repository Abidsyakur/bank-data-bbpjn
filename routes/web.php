<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware(['auth', 'aktif'])->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/ganti-sandi', [PasswordController::class, 'edit'])->name('sandi.edit');
    Route::put('/ganti-sandi', [PasswordController::class, 'update'])->name('sandi.update');

    // Dokumen: hak akses diatur di DokumenPolicy
    Route::get('dokumen/{dokumen}/berkas', [DokumenController::class, 'berkas'])->name('dokumen.berkas');
    Route::resource('dokumen', DokumenController::class)->parameters(['dokumen' => 'dokumen']);

    // Khusus Pimpinan
    Route::middleware('role:pimpinan')->group(function () {
        Route::resource('akun', AkunController::class)
            ->except(['show', 'destroy'])
            ->parameters(['akun' => 'user']);
        Route::patch('akun/{user}/toggle', [AkunController::class, 'toggle'])->name('akun.toggle');
    });
});
