<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Masuk - Bank Data Terpusat BBPJN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-concrete font-sans text-ink antialiased">
<div class="min-h-screen lg:grid lg:grid-cols-2">

    <section class="relative bg-nav px-8 py-10 text-white lg:flex lg:flex-col lg:justify-center lg:px-16">
        {{-- Marka jalan putus-putus di tepi panel --}}
        <div class="absolute inset-y-0 right-0 hidden w-1 lg:block"
             style="background: repeating-linear-gradient(to bottom, rgba(255,255,255,.35) 0 28px, transparent 28px 56px)"></div>
        <h1 class="text-3xl font-semibold leading-tight">Bank Data Terpusat</h1>
        <p class="mt-2 text-white/70">BBPJN Sumatera Selatan</p>
        <p class="mt-8 max-w-sm text-white/80">
            Arsip PPID, SP4N-LAPOR!, dan JAKI dikelola dalam satu aplikasi.
        </p>
    </section>

    <section class="flex items-center px-6 py-12 lg:px-16">
        <form method="POST" action="{{ route('login') }}" class="mx-auto w-full max-w-sm">
            @csrf
            <h2 class="text-2xl font-semibold">Masuk</h2>
            <p class="mt-1 text-sm text-ink/70">Gunakan akun yang dibuat oleh Pimpinan.</p>

            @error('username')
                <div role="alert" class="mt-5 rounded-md border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">
                    {{ $message }}
                </div>
            @enderror

            <label for="username" class="mt-6 block text-sm font-medium">Username</label>
            <input id="username" name="username" type="text" value="{{ old('username') }}" required autofocus
                   autocomplete="username"
                   class="mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary">

            <label for="password" class="mt-4 block text-sm font-medium">Kata sandi</label>
            <input id="password" name="password" type="password" required autocomplete="current-password"
                   class="mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary">

            <label class="mt-4 flex items-center gap-2 text-sm">
                <input type="checkbox" name="remember" value="1" class="size-4 accent-primary">
                Ingat saya di perangkat ini
            </label>

            <button type="submit"
                    class="mt-6 w-full rounded-md bg-primary px-4 py-2.5 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Masuk
            </button>
        </form>
    </section>
</div>
</body>
</html>
