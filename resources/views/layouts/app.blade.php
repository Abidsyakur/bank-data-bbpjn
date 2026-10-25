@php
    $user = auth()->user()->loadMissing('role');
    $layananMenu = \App\Models\Layanan::untukUser($user)->orderBy('nama')->get();

    $dok = request()->route('dokumen');
    $aktifKode = request('layanan') ?: ($dok instanceof \App\Models\Dokumen ? $dok->layanan->kode : null);
    $diDokumen = request()->routeIs('dokumen.*');
    $inisial = mb_strtoupper(mb_substr($user->nama, 0, 1));

    $item = fn (bool $aktif) => 'block border-l-4 px-4 py-2.5 text-sm focus-visible:outline-2 focus-visible:outline-accent '
        . ($aktif ? 'border-accent bg-white/10 font-medium text-white' : 'border-transparent text-white/75 hover:bg-white/5');
    $judulGrup = 'group flex cursor-pointer list-none items-center justify-between px-4 py-3 text-xs font-semibold tracking-wide text-white/80 focus-visible:outline-2 focus-visible:outline-accent [&::-webkit-details-marker]:hidden';
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard') - Bank Data Terpusat BBPJN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-concrete font-sans text-ink antialiased">
<div class="min-h-screen lg:flex">

    <aside class="shrink-0 bg-nav text-white lg:sticky lg:top-0 lg:h-screen lg:w-64 lg:overflow-y-auto">
        <div class="bg-white px-4 py-3">
            <a href="{{ route('dashboard') }}" class="block focus-visible:outline-2 focus-visible:outline-primary">
                <img src="{{ asset('images/logo-bbpjn.png') }}" alt="BBPJN Sumatera Selatan" width="270" height="83" class="h-auto w-full max-w-[190px]">
            </a>
        </div>
        <div class="px-4 py-3">
            <p class="text-sm font-semibold leading-tight">Bank Data Terpusat</p>
        </div>

        <nav aria-label="Menu utama">
            <details open class="group border-t border-white/10">
                <summary class="{{ $judulGrup }}">
                    <span>UTAMA</span>
                    <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.5 10 12.3l4.8-4.8 1.1 1.1L10 14.5 4.1 8.6z"/></svg>
                </summary>
                <div class="bg-nav-deep py-1">
                    <a href="{{ route('dashboard') }}" class="{{ $item(request()->routeIs('dashboard')) }}">Dashboard</a>
                </div>
            </details>

            <details open class="group border-t border-white/10">
                <summary class="{{ $judulGrup }}">
                    <span>ARSIP DOKUMEN</span>
                    <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.5 10 12.3l4.8-4.8 1.1 1.1L10 14.5 4.1 8.6z"/></svg>
                </summary>
                <div class="bg-nav-deep py-1">
                    <a href="{{ route('dokumen.index') }}" class="{{ $item($diDokumen && ! $aktifKode) }}">
                        {{ $user->isPimpinan() ? 'Semua dokumen' : 'Semua layanan saya' }}
                    </a>
                    @foreach ($layananMenu as $l)
                        <a href="{{ route('dokumen.index', ['layanan' => $l->kode]) }}" class="{{ $item($diDokumen && $aktifKode === $l->kode) }}">
                            {{ $l->nama }}
                        </a>
                    @endforeach
                </div>
            </details>

            @if ($user->isPimpinan())
                <details open class="group border-t border-white/10">
                    <summary class="{{ $judulGrup }}">
                        <span>LAPORAN</span>
                        <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.5 10 12.3l4.8-4.8 1.1 1.1L10 14.5 4.1 8.6z"/></svg>
                    </summary>
                    <div class="bg-nav-deep py-1">
                        <a href="{{ route('laporan.index') }}" class="{{ $item(request()->routeIs('laporan.*')) }}">Laporan rekapitulasi</a>
                        <a href="{{ route('riwayat.index') }}" class="{{ $item(request()->routeIs('riwayat.*')) }}">Riwayat aktivitas</a>
                    </div>
                </details>
            @endif

            @if ($user->isPimpinan())
                <details open class="group border-t border-white/10">
                    <summary class="{{ $judulGrup }}">
                        <span>PENGATURAN</span>
                        <svg class="size-4 transition group-open:rotate-180" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path d="M5.2 7.5 10 12.3l4.8-4.8 1.1 1.1L10 14.5 4.1 8.6z"/></svg>
                    </summary>
                    <div class="bg-nav-deep py-1">
                        <a href="{{ route('akun.index') }}" class="{{ $item(request()->routeIs('akun.*')) }}">Manajemen akun</a>
                        <a href="{{ route('backup.index') }}" class="{{ $item(request()->routeIs('backup.*')) }}">Backup &amp; restore</a>
                    </div>
                </details>
            @endif
        </nav>
    </aside>

    <div class="flex min-w-0 flex-1 flex-col">
        <header class="flex items-center justify-between gap-4 border-b border-line bg-white px-5 py-2.5 lg:px-10">
            <form method="GET" action="{{ route('dokumen.index') }}" role="search" class="w-full max-w-sm">
                <label for="cari-cepat" class="sr-only">Cari dokumen</label>
                <input id="cari-cepat" name="q" type="search" value="{{ request('q') }}" placeholder="Cari nomor surat atau judul"
                       class="w-full rounded-md border border-line bg-concrete px-3 py-2 text-sm focus:border-ink focus:bg-white focus:outline-2 focus:outline-primary">
            </form>
            <details class="relative">
                <summary class="flex cursor-pointer list-none items-center gap-3 [&::-webkit-details-marker]:hidden">
                    <span class="grid size-9 place-items-center rounded-full bg-nav text-sm font-semibold text-white">{{ $inisial }}</span>
                    <span class="text-left text-sm leading-tight">
                        <span class="block font-medium">{{ $user->nama }}</span>
                        <span class="block text-ink/60">{{ $user->role->nama }}</span>
                    </span>
                </summary>
                <div class="absolute right-0 z-10 mt-2 w-52 rounded-md border border-line bg-white py-1 text-sm shadow-sm">
                    <a href="{{ route('sandi.edit') }}" class="block px-4 py-2 hover:bg-concrete">Ganti kata sandi</a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="block w-full px-4 py-2 text-left hover:bg-concrete">Keluar</button>
                    </form>
                </div>
            </details>
        </header>

        <main class="flex-1 px-5 py-6 lg:px-10 lg:py-8">
            @if (session('status'))
                <div role="status" class="mb-5 rounded-md border border-ok/30 bg-ok/10 px-4 py-3 text-sm text-ok">
                    {{ session('status') }}
                </div>
            @endif
            @if ($errors->has('akun'))
                <div role="alert" class="mb-5 rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                    {{ $errors->first('akun') }}
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>
</body>
</html>
