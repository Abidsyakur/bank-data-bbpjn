<?php

namespace Tests\Unit;

use App\Models\Dokumen;
use App\Models\Layanan;
use App\Models\Role;
use App\Models\User;
use App\Policies\DokumenPolicy;
use Tests\TestCase;

/** Menguji aturan akses dokumen tanpa basis data, memakai model dalam memori. */
class DokumenPolicyTest extends TestCase
{
    private const ROLE_PP = 2;
    private const ROLE_SP4N = 3;

    private function user(string $kode, int $roleId): User
    {
        $user = new User(['role_id' => $roleId]);
        $user->setRelation('role', new Role(['kode' => $kode]));

        return $user;
    }

    private function dokumenMilik(int $roleIdLayanan): Dokumen
    {
        $dokumen = new Dokumen();
        $dokumen->setRelation('layanan', new Layanan(['role_id' => $roleIdLayanan]));

        return $dokumen;
    }

    public function test_semua_pengguna_boleh_membuka_daftar(): void
    {
        $this->assertTrue((new DokumenPolicy())->viewAny($this->user('admin_pp', self::ROLE_PP)));
        $this->assertTrue((new DokumenPolicy())->viewAny($this->user('pimpinan', 1)));
    }

    public function test_pimpinan_hanya_boleh_melihat(): void
    {
        $policy = new DokumenPolicy();
        $pimpinan = $this->user('pimpinan', 1);
        $dokumen = $this->dokumenMilik(self::ROLE_PP);

        $this->assertTrue($policy->view($pimpinan, $dokumen));
        $this->assertFalse($policy->create($pimpinan));
        $this->assertFalse($policy->update($pimpinan, $dokumen));
        $this->assertFalse($policy->delete($pimpinan, $dokumen));
    }

    public function test_admin_pemilik_layanan_boleh_mengelola_dokumennya(): void
    {
        $policy = new DokumenPolicy();
        $admin = $this->user('admin_pp', self::ROLE_PP);
        $dokumen = $this->dokumenMilik(self::ROLE_PP);

        $this->assertTrue($policy->view($admin, $dokumen));
        $this->assertTrue($policy->create($admin));
        $this->assertTrue($policy->update($admin, $dokumen));
        $this->assertTrue($policy->delete($admin, $dokumen));
    }

    public function test_admin_tidak_boleh_menyentuh_dokumen_layanan_lain(): void
    {
        $policy = new DokumenPolicy();
        $admin = $this->user('admin_sp4n', self::ROLE_SP4N);
        $dokumen = $this->dokumenMilik(self::ROLE_PP);

        $this->assertFalse($policy->view($admin, $dokumen));
        $this->assertFalse($policy->update($admin, $dokumen));
        $this->assertFalse($policy->delete($admin, $dokumen));
    }
}
