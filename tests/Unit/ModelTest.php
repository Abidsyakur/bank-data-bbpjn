<?php

namespace Tests\Unit;

use App\Models\Dokumen;
use App\Models\Role;
use App\Models\User;
use Tests\TestCase;

class ModelTest extends TestCase
{
    private function userDenganRole(string $kode): User
    {
        $user = new User(['role_id' => 1]);
        $user->setRelation('role', new Role(['kode' => $kode, 'nama' => ucfirst($kode)]));

        return $user;
    }

    public function test_has_role_mengenali_peran_pengguna(): void
    {
        $user = $this->userDenganRole('admin_jaki');

        $this->assertTrue($user->hasRole('admin_jaki'));
        $this->assertTrue($user->hasRole('admin_pp', 'admin_jaki'));
        $this->assertFalse($user->hasRole('pimpinan'));
    }

    public function test_hanya_pimpinan_dikenali_sebagai_pimpinan(): void
    {
        $this->assertTrue($this->userDenganRole('pimpinan')->isPimpinan());
        $this->assertFalse($this->userDenganRole('admin_pp')->isPimpinan());
    }

    public function test_kata_sandi_disimpan_dalam_bentuk_hash_dan_disembunyikan(): void
    {
        $user = new User(['nama' => 'Uji', 'username' => 'uji', 'password' => 'rahasia123']);

        $this->assertNotSame('rahasia123', $user->password);
        $this->assertTrue(password_verify('rahasia123', $user->password));
        $this->assertArrayNotHasKey('password', $user->toArray());
    }

    public function test_label_status_dokumen(): void
    {
        $this->assertSame('Menunggu', (new Dokumen(['status' => Dokumen::STATUS_MENUNGGU]))->labelStatus());
        $this->assertSame('Selesai', (new Dokumen(['status' => Dokumen::STATUS_SELESAI]))->labelStatus());
        $this->assertSame('-', (new Dokumen(['status' => null]))->labelStatus());
    }

    public function test_konstanta_status_sesuai_nilai_di_basis_data(): void
    {
        $this->assertSame('menunggu', Dokumen::STATUS_MENUNGGU);
        $this->assertSame('selesai', Dokumen::STATUS_SELESAI);
    }
}
