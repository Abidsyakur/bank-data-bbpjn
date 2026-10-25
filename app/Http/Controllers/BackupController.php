<?php

namespace App\Http\Controllers;

use App\Services\ActivityLogger;
use App\Services\BackupService;
use App\Support\PesanValidasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

/** Khusus Pimpinan (dijaga middleware role:pimpinan di routes). */
class BackupController extends Controller
{
    public function __construct(private BackupService $backup)
    {
    }

    public function index(): View
    {
        $riwayat = DB::table('backup')
            ->join('users', 'users.id', '=', 'backup.user_id')
            ->select('backup.*', 'users.nama')
            ->orderByDesc('backup.waktu')
            ->orderByDesc('backup.id')
            ->limit(10)
            ->get();

        return view('backup.index', ['daftar' => $this->backup->daftar(), 'riwayat' => $riwayat]);
    }

    public function store(Request $request): RedirectResponse
    {
        return $this->denganKunci(function () use ($request) {
            try {
                $nama = $this->backup->buat('backup', $request->user());
            } catch (RuntimeException $e) {
                return back()->withErrors(['akun' => 'Backup gagal dibuat: ' . $e->getMessage()]);
            } catch (Throwable $e) {
                report($e);

                return back()->withErrors(['akun' => 'Backup gagal dibuat karena terjadi kesalahan. Periksa log aplikasi.']);
            }

            $this->catatRiwayat($request->user()->id, 'backup', $nama);
            ActivityLogger::catat('backup.buat', $nama);

            return redirect()->route('backup.index')->with('status', "Backup {$nama} berhasil dibuat.");
        });
    }

    public function unduh(string $berkas): BinaryFileResponse
    {
        $path = $this->backup->jalur($berkas);
        ActivityLogger::catat('backup.unduh', $berkas);

        return response()->download($path, $berkas);
    }

    public function destroy(string $berkas): RedirectResponse
    {
        $this->backup->jalur($berkas);
        $this->backup->hapus($berkas);
        ActivityLogger::catat('backup.hapus', $berkas);

        return redirect()->route('backup.index')->with('status', "Backup {$berkas} dihapus dari server.");
    }

    public function unggah(Request $request): RedirectResponse
    {
        $request->validate([
            'berkas_backup' => ['required', 'file', 'mimes:zip', 'max:2097152'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $nama = $this->backup->simpanUnggahan($request->file('berkas_backup'));

        try {
            $this->backup->periksa($nama);
        } catch (RuntimeException $e) {
            $this->backup->hapus($nama);

            return back()->withErrors(['berkas_backup' => $e->getMessage()]);
        }

        ActivityLogger::catat('backup.unggah', $nama);

        return redirect()->route('backup.konfirmasi', $nama);
    }

    public function konfirmasi(string $berkas): View|RedirectResponse
    {
        try {
            $manifest = $this->backup->periksa($berkas);
        } catch (RuntimeException $e) {
            return redirect()->route('backup.index')->withErrors(['akun' => $e->getMessage()]);
        }

        return view('backup.konfirmasi', compact('berkas', 'manifest'));
    }

    public function pulihkan(Request $request, string $berkas): RedirectResponse
    {
        $this->backup->jalur($berkas);

        $request->validate([
            'konfirmasi' => ['required', Rule::in(['PULIHKAN'])],
            'password'   => ['required', 'current_password'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $idUser = $request->user()->id;

        return $this->denganKunci(function () use ($request, $berkas, $idUser) {
            try {
                $pra = $this->backup->pulihkan($berkas, $request->user());
            } catch (RuntimeException $e) {
                return redirect()->route('backup.konfirmasi', $berkas)
                    ->withErrors(['akun' => 'Pemulihan dibatalkan dan data tidak diubah: ' . $e->getMessage()]);
            } catch (Throwable $e) {
                report($e);

                return redirect()->route('backup.konfirmasi', $berkas)
                    ->withErrors(['akun' => 'Pemulihan gagal dan data dikembalikan seperti semula. Periksa log aplikasi.']);
            }

            // Akun pemulih mungkin tidak ada pada data backup.
            if (! DB::table('users')->where('id', $idUser)->exists()) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return redirect()->route('login')->withErrors([
                    'username' => 'Pemulihan selesai. Akun Anda tidak ada pada backup tersebut, silakan masuk kembali.',
                ]);
            }

            $this->catatRiwayat($idUser, 'backup', $pra);
            $this->catatRiwayat($idUser, 'restore', $berkas);
            ActivityLogger::catat('restore.jalankan', "Dari {$berkas}; backup pengaman {$pra}");

            return redirect()->route('backup.index')
                ->with('status', "Data berhasil dipulihkan dari {$berkas}. Kondisi sebelumnya tersimpan sebagai {$pra}.");
        });
    }

    private function catatRiwayat(int $userId, string $jenis, string $berkas): void
    {
        DB::table('backup')->insert([
            'user_id'   => $userId,
            'jenis'     => $jenis,
            'file_path' => $berkas,
            'waktu'     => now(),
        ]);
    }

    /** Mencegah dua proses backup/restore berjalan bersamaan. */
    private function denganKunci(callable $aksi): RedirectResponse
    {
        $kunci = Cache::lock('operasi-backup', 1800);

        if (! $kunci->get()) {
            return back()->withErrors(['akun' => 'Proses backup atau restore lain sedang berjalan. Coba lagi beberapa saat.']);
        }

        try {
            return $aksi();
        } finally {
            $kunci->release();
        }
    }
}
