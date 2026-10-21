<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Support\PesanValidasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AkunController extends Controller
{
    public function index(): View
    {
        $users = User::with('role')->orderBy('nama')->get();

        return view('akun.index', compact('users'));
    }

    public function create(): View
    {
        return view('akun.create', ['roles' => Role::orderBy('id')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'nama'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', 'unique:users,username'],
            'role_id'  => ['required', 'exists:roles,id'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $user = User::create($data + ['aktif' => true]);
        ActivityLogger::catat('akun.tambah', "Akun {$user->username} dibuat");

        return redirect()->route('akun.index')->with('status', "Akun {$user->username} berhasil dibuat.");
    }

    public function edit(User $user): View
    {
        return view('akun.edit', [
            'user'  => $user,
            'roles' => Role::orderBy('id')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'nama'     => ['required', 'string', 'max:100'],
            'username' => ['required', 'alpha_dash', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'role_id'  => ['required', 'exists:roles,id'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], PesanValidasi::pesan(), PesanValidasi::atribut());

        $data['aktif'] = $request->boolean('aktif');

        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Pimpinan tidak boleh menurunkan peran atau menonaktifkan akunnya sendiri.
        if ($user->id === $request->user()->id) {
            unset($data['role_id']);
            $data['aktif'] = true;
        }

        $user->update($data);
        ActivityLogger::catat('akun.ubah', "Akun {$user->username} diubah");

        return redirect()->route('akun.index')->with('status', "Akun {$user->username} berhasil diperbarui.");
    }

    public function toggle(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['akun' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        $user->update(['aktif' => ! $user->aktif]);

        $aksi = $user->aktif ? 'akun.aktifkan' : 'akun.nonaktifkan';
        ActivityLogger::catat($aksi, "Akun {$user->username}");

        return back()->with('status', 'Akun ' . $user->username . ($user->aktif ? ' diaktifkan.' : ' dinonaktifkan.'));
    }
}
