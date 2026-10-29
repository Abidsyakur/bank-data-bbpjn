<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AkunTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    private User $pimpinan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataDasar();
        $this->pimpinan = $this->buatUser('pimpinan');
        $this->actingAs($this->pimpinan);
    }

    private function roleId(string $kode): int
    {
        return Role::where('kode', $kode)->value('id');
    }

    private function dataAkunBaru(array $ubah = []): array
    {
        return array_merge([
            'nama'                  => 'Petugas Uji',
            'username'              => 'petugas_uji',
            'role_id'               => $this->roleId('admin_jaki'),
            'password'              => 'sandi12345',
            'password_confirmation' => 'sandi12345',
        ], $ubah);
    }

    public function test_tambah_akun_valid(): void
    {
        $this->post(route('akun.store'), $this->dataAkunBaru())
            ->assertRedirect(route('akun.index'))
            ->assertSessionHas('status');

        $akun = User::where('username', 'petugas_uji')->firstOrFail();
        $this->assertTrue($akun->aktif);
        $this->assertTrue(Hash::check('sandi12345', $akun->password));
        $this->assertDatabaseHas('log_aktivitas', ['user_id' => $this->pimpinan->id, 'aksi' => 'akun.tambah']);
    }

    public function test_username_yang_sudah_dipakai_ditolak(): void
    {
        $this->buatUser('admin_pp');

        $this->post(route('akun.store'), $this->dataAkunBaru(['username' => 'admin_pp']))
            ->assertSessionHasErrors(['username' => 'Username sudah dipakai.']);
    }

    public function test_username_dengan_spasi_ditolak(): void
    {
        $this->post(route('akun.store'), $this->dataAkunBaru(['username' => 'petugas uji']))
            ->assertSessionHasErrors('username');

        $this->assertDatabaseMissing('users', ['nama' => 'Petugas Uji']);
    }

    public function test_kata_sandi_pendek_atau_konfirmasi_berbeda_ditolak(): void
    {
        $this->post(route('akun.store'), $this->dataAkunBaru(['password' => 'abc', 'password_confirmation' => 'abc']))
            ->assertSessionHasErrors(['password' => 'Kata sandi minimal 8 karakter.']);

        $this->post(route('akun.store'), $this->dataAkunBaru(['password_confirmation' => 'beda12345']))
            ->assertSessionHasErrors('password');
    }

    public function test_peran_yang_tidak_ada_ditolak(): void
    {
        $this->post(route('akun.store'), $this->dataAkunBaru(['role_id' => 9999]))
            ->assertSessionHasErrors('role_id');
    }

    public function test_ubah_akun_tanpa_mengisi_kata_sandi_mempertahankan_kata_sandi_lama(): void
    {
        $akun = $this->buatUser('admin_pp', 'petugas');

        $this->put(route('akun.update', $akun), [
            'nama'     => 'Nama Baru',
            'username' => 'petugas',
            'role_id'  => $this->roleId('admin_jaki'),
            'aktif'    => '1',
        ])->assertRedirect(route('akun.index'));

        $akun->refresh();
        $this->assertSame('Nama Baru', $akun->nama);
        $this->assertSame('admin_jaki', $akun->role->kode);
        $this->assertTrue(Hash::check('password123', $akun->password));
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.ubah']);
    }

    public function test_ubah_akun_dengan_kata_sandi_baru(): void
    {
        $akun = $this->buatUser('admin_pp', 'petugas');

        $this->put(route('akun.update', $akun), [
            'nama'                  => 'Petugas',
            'username'              => 'petugas',
            'role_id'               => $akun->role_id,
            'password'              => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
            'aktif'                 => '1',
        ])->assertRedirect(route('akun.index'));

        $this->assertTrue(Hash::check('sandi-baru-123', $akun->fresh()->password));
    }

    public function test_nonaktifkan_dan_aktifkan_kembali_akun(): void
    {
        $akun = $this->buatUser('admin_pp', 'petugas');

        $this->patch(route('akun.toggle', $akun))->assertSessionHas('status');
        $this->assertFalse($akun->fresh()->aktif);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.nonaktifkan']);

        $this->patch(route('akun.toggle', $akun))->assertSessionHas('status');
        $this->assertTrue($akun->fresh()->aktif);
        $this->assertDatabaseHas('log_aktivitas', ['aksi' => 'akun.aktifkan']);
    }

    public function test_pimpinan_tidak_dapat_menonaktifkan_akunnya_sendiri(): void
    {
        $this->patch(route('akun.toggle', $this->pimpinan))->assertSessionHasErrors('akun');

        $this->assertTrue($this->pimpinan->fresh()->aktif);
    }

    public function test_pimpinan_tidak_dapat_menurunkan_peran_atau_menonaktifkan_diri_lewat_formulir(): void
    {
        $this->put(route('akun.update', $this->pimpinan), [
            'nama'     => $this->pimpinan->nama,
            'username' => $this->pimpinan->username,
            'role_id'  => $this->roleId('admin_pp'),
            'aktif'    => '0',
        ])->assertRedirect(route('akun.index'));

        $segar = $this->pimpinan->fresh();
        $this->assertSame('pimpinan', $segar->role->kode);
        $this->assertTrue($segar->aktif);
    }

    public function test_daftar_akun_menampilkan_semua_pengguna(): void
    {
        $this->buatUser('admin_pp', 'petugas_a');

        $this->get(route('akun.index'))->assertOk()->assertSee('petugas_a')->assertSee('pimpinan');
    }
}
