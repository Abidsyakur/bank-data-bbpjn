<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\BackupController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DokumenController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\RiwayatController;
use App\Http\Controllers\TindakLanjutController;
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

    // Tindak lanjut (status Menunggu -> Selesai)
    Route::post('dokumen/{dokumen}/tindak-lanjut', [TindakLanjutController::class, 'store'])->name('tindak-lanjut.store');
    Route::patch('dokumen/{dokumen}/buka-kembali', [TindakLanjutController::class, 'bukaKembali'])->name('tindak-lanjut.buka-kembali');
    Route::get('tindak-lanjut/{tindakLanjut}/bukti', [TindakLanjutController::class, 'bukti'])->name('tindak-lanjut.bukti');
    Route::delete('tindak-lanjut/{tindakLanjut}', [TindakLanjutController::class, 'destroy'])->name('tindak-lanjut.destroy');

    // Khusus Pimpinan
    Route::middleware('role:pimpinan')->group(function () {
        // Backup dan restore
        $pola = \App\Services\BackupService::POLA_NAMA;
        Route::get('backup', [BackupController::class, 'index'])->name('backup.index');
        Route::post('backup', [BackupController::class, 'store'])->name('backup.store');
        Route::post('backup/unggah', [BackupController::class, 'unggah'])->name('backup.unggah');
        Route::get('backup/{berkas}/unduh', [BackupController::class, 'unduh'])->where('berkas', $pola)->name('backup.unduh');
        Route::delete('backup/{berkas}', [BackupController::class, 'destroy'])->where('berkas', $pola)->name('backup.destroy');
        Route::get('backup/{berkas}/pulihkan', [BackupController::class, 'konfirmasi'])->where('berkas', $pola)->name('backup.konfirmasi');
        Route::post('backup/{berkas}/pulihkan', [BackupController::class, 'pulihkan'])->where('berkas', $pola)->name('backup.pulihkan');

        Route::get('riwayat', RiwayatController::class)->name('riwayat.index');

        Route::get('laporan', [LaporanController::class, 'index'])->name('laporan.index');
        Route::get('laporan/pdf', [LaporanController::class, 'pdf'])->name('laporan.pdf');
        Route::get('laporan/excel', [LaporanController::class, 'excel'])->name('laporan.excel');

        Route::resource('akun', AkunController::class)
            ->except(['show', 'destroy'])
            ->parameters(['akun' => 'user']);
        Route::patch('akun/{user}/toggle', [AkunController::class, 'toggle'])->name('akun.toggle');
    });
});
