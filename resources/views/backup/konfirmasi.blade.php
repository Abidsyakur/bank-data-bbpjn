@extends('layouts.app')

@section('title', 'Konfirmasi pemulihan data')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
        $ukuran = fn ($b) => \App\Support\Format::ukuran($b);
        $nama = [
            'roles' => 'Peran', 'users' => 'Akun pengguna', 'layanan' => 'Layanan', 'dokumen' => 'Dokumen',
            'tindak_lanjut' => 'Tindak lanjut', 'log_aktivitas' => 'Log aktivitas', 'backup' => 'Riwayat backup',
        ];
    @endphp

    <h1 class="text-xl font-semibold">Konfirmasi pemulihan data</h1>
    <p class="mt-1 text-sm text-ink/60">Berkas: {{ $berkas }}</p>

    <div class="mt-6 max-w-2xl rounded-md border border-danger/40 bg-danger/10 p-5 text-sm text-danger">
        <p class="font-semibold">Perhatian: tindakan ini mengganti seluruh data saat ini.</p>
        <ul class="mt-2 list-disc space-y-1 pl-5">
            <li>Semua data dan berkas arsip yang ada sekarang diganti dengan isi backup ini, termasuk akun dan kata sandi pengguna.</li>
            <li>Data yang dibuat setelah backup ini dibuat akan hilang dari aplikasi.</li>
            <li>Sebelum diganti, sistem membuat backup pengaman otomatis (berawalan <em>pra-restore</em>) yang tetap tersimpan di server.</li>
            <li>Jika terjadi kesalahan di tengah proses, data dikembalikan seperti semula.</li>
        </ul>
        <p class="mt-2">Sebaiknya lakukan di luar jam kerja, saat tidak ada pengguna lain yang sedang bekerja.</p>
    </div>

    <h2 class="mt-8 text-lg font-semibold">Isi backup</h2>
    <dl class="mt-3 grid max-w-2xl gap-x-6 gap-y-4 rounded-md border border-line bg-white p-5 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-ink/60">Dibuat pada</dt>
            <dd class="mt-0.5 font-medium">{{ \Illuminate\Support\Carbon::parse($manifest['dibuat'])->timezone(config('app.timezone'))->format('d/m/Y H:i') }}</dd>
        </div>
        <div>
            <dt class="text-ink/60">Dibuat oleh</dt>
            <dd class="mt-0.5 font-medium">{{ $manifest['dibuat_oleh'] ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-ink/60">Berkas arsip</dt>
            <dd class="mt-0.5 font-medium">{{ $manifest['berkas']['jumlah'] }} berkas ({{ $ukuran($manifest['berkas']['ukuran']) }})</dd>
        </div>
        @foreach ($nama as $kunci => $label)
            <div>
                <dt class="text-ink/60">{{ $label }}</dt>
                <dd class="mt-0.5 font-medium">{{ $manifest['tabel'][$kunci]['jumlah'] }}</dd>
            </div>
        @endforeach
    </dl>

    <form method="POST" action="{{ route('backup.pulihkan', $berkas) }}" class="mt-6 max-w-md"
          onsubmit="const b = this.querySelector('button'); b.disabled = true; b.textContent = 'Memulihkan data...'">
        @csrf

        <label for="konfirmasi" class="block text-sm font-medium">Ketik <strong>PULIHKAN</strong> untuk melanjutkan</label>
        <input id="konfirmasi" name="konfirmasi" type="text" required autocomplete="off" class="{{ $input }}">
        @error('konfirmasi') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

        <label for="password" class="mt-4 block text-sm font-medium">Kata sandi Anda</label>
        <input id="password" name="password" type="password" required autocomplete="current-password" class="{{ $input }}">
        @error('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

        <div class="mt-6 flex items-center gap-4">
            <button type="submit"
                    class="rounded-md bg-danger px-4 py-2 text-sm font-semibold text-white hover:brightness-110 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Pulihkan data
            </button>
            <a href="{{ route('backup.index') }}" class="text-sm underline underline-offset-2">Batal</a>
        </div>
    </form>
@endsection
