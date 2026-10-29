<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\Concerns\MembuatDataUji;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, MembuatDataUji;

    protected function setUp(): void
    {
        parent::setUp();
        $this->siapkanDataDasar();
    }

    public function test_halaman_login_dapat_dibuka_tamu(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_alamat_utama_mengarah_ke_dashboard(): void
    {
        $this->get('/')->assertRedirect('/dashboard');
    }

    public function test_login_berhasil_mengarah_ke_dashboard_dan_tercatat_di_log(): void
    {
        $user = $this->buatUser('pimpinan');

        $this->post('/login', ['username' => 'pimpinan', 'password' => 'password123'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('log_aktivitas', ['user_id' => $user->id, 'aksi' => 'login']);
    }

    public function test_kata_sandi_salah_ditolak(): void
    {
        $this->buatUser('pimpinan');

        $this->post('/login', ['username' => 'pimpinan', 'password' => 'salah'])
            ->assertSessionHasErrors(['username' => 'Username atau kata sandi salah, atau akun tidak aktif.']);

        $this->assertGuest();
    }

    public function test_username_tidak_terdaftar_mendapat_pesan_yang_sama(): void
    {
        $this->post('/login', ['username' => 'tidakada', 'password' => 'password123'])
            ->assertSessionHasErrors(['username' => 'Username atau kata sandi salah, atau akun tidak aktif.']);

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_dapat_login(): void
    {
        $this->buatUser('admin_pp', 'nonaktif', false);

        $this->post('/login', ['username' => 'nonaktif', 'password' => 'password123'])
            ->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_percobaan_login_dibatasi_lima_kali_per_menit(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['username' => 'x', 'password' => 'y'])->assertStatus(302);
        }

        $this->post('/login', ['username' => 'x', 'password' => 'y'])->assertStatus(429);
    }

    public function test_tamu_diarahkan_ke_login_dari_halaman_terproteksi(): void
    {
        foreach (['/dashboard', '/dokumen', '/ganti-sandi'] as $alamat) {
            $this->get($alamat)->assertRedirect('/login');
        }
    }

    public function test_logout_mengakhiri_sesi_dan_tercatat(): void
    {
        $user = $this->buatUser('admin_pp');

        $this->actingAs($user)->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->assertDatabaseHas('log_aktivitas', ['user_id' => $user->id, 'aksi' => 'logout']);
    }

    public function test_akun_yang_dinonaktifkan_saat_sesi_berjalan_dikeluarkan(): void
    {
        $user = $this->buatUser('admin_pp');
        $this->actingAs($user);

        $user->update(['aktif' => false]);

        $this->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('username');
    }

    public function test_ganti_kata_sandi_berhasil(): void
    {
        $user = $this->buatUser('admin_pp');

        $this->actingAs($user)->put('/ganti-sandi', [
            'current_password'      => 'password123',
            'password'              => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertRedirect(route('sandi.edit'))->assertSessionHas('status');

        $this->assertTrue(Hash::check('sandi-baru-123', $user->fresh()->password));
        $this->assertDatabaseHas('log_aktivitas', ['user_id' => $user->id, 'aksi' => 'ganti_sandi']);
    }

    public function test_ganti_kata_sandi_ditolak_bila_sandi_lama_salah(): void
    {
        $user = $this->buatUser('admin_pp');

        $this->actingAs($user)->put('/ganti-sandi', [
            'current_password'      => 'bukan-sandi-saya',
            'password'              => 'sandi-baru-123',
            'password_confirmation' => 'sandi-baru-123',
        ])->assertSessionHasErrors(['current_password' => 'Kata sandi saat ini salah.']);

        $this->assertTrue(Hash::check('password123', $user->fresh()->password));
    }

    public function test_ganti_kata_sandi_ditolak_bila_kurang_dari_delapan_karakter(): void
    {
        $user = $this->buatUser('admin_pp');

        $this->actingAs($user)->put('/ganti-sandi', [
            'current_password'      => 'password123',
            'password'              => 'abc',
            'password_confirmation' => 'abc',
        ])->assertSessionHasErrors(['password' => 'Kata sandi minimal 8 karakter.']);
    }

    public function test_ganti_kata_sandi_ditolak_bila_konfirmasi_berbeda(): void
    {
        $user = $this->buatUser('admin_pp');

        $this->actingAs($user)->put('/ganti-sandi', [
            'current_password'      => 'password123',
            'password'              => 'sandi-baru-123',
            'password_confirmation' => 'sandi-lain-456',
        ])->assertSessionHasErrors('password');
    }
}
