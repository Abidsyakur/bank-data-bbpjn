<?php

namespace App\Policies;

use App\Models\Dokumen;
use App\Models\User;

/**
 * Admin: kelola dokumen layanan miliknya saja.
 * Pimpinan: hanya melihat dan mengunduh semua dokumen.
 */
class DokumenPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Dokumen $dokumen): bool
    {
        return $user->isPimpinan() || $this->milikRole($user, $dokumen);
    }

    public function create(User $user): bool
    {
        return ! $user->isPimpinan();
    }

    public function update(User $user, Dokumen $dokumen): bool
    {
        return ! $user->isPimpinan() && $this->milikRole($user, $dokumen);
    }

    public function delete(User $user, Dokumen $dokumen): bool
    {
        return $this->update($user, $dokumen);
    }

    private function milikRole(User $user, Dokumen $dokumen): bool
    {
        return $dokumen->layanan->role_id === $user->role_id;
    }
}
