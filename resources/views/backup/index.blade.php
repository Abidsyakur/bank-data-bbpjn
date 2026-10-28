@extends('layouts.app')

@section('title', 'Backup dan restore')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
        $ukuran = fn ($b) => \App\Support\Format::ukuran($b);
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold text-primary">Backup dan restore</h1>
            <p class="mt-1 max-w-2xl text-sm text-ink/60">
                Backup memuat seluruh data aplikasi dan semua berkas arsip dalam satu berkas ZIP.
                Unduh dan simpan salinannya di luar server ini, misalnya di komputer lain atau penyimpanan eksternal.
            </p>
        </div>
        <form method="POST" action="{{ route('backup.store') }}" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').textContent = 'Membuat backup...'">
            @csrf
            <button type="submit"
                    class="rounded-md bg-accent px-4 py-2 text-sm font-semibold text-primary hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Buat backup sekarang
            </button>
        </form>
    </div>

    <h2 class="mt-8 text-lg font-semibold">Backup di server</h2>
    <div class="mt-3 overflow-x-auto rounded-md border border-line bg-white">
        @if ($daftar->isEmpty())
            <p class="px-6 py-12 text-center text-sm text-ink/60">Belum ada backup di server. Klik "Buat backup sekarang".</p>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-concrete">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Berkas</th>
                        <th class="px-4 py-2.5 font-medium">Jenis</th>
                        <th class="px-4 py-2.5 font-medium">Ukuran</th>
                        <th class="px-4 py-2.5 font-medium">Dibuat</th>
                        <th class="px-4 py-2.5 font-medium">Tindakan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($daftar as $b)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-2.5 font-medium">{{ $b['nama'] }}</td>
                            <td class="px-4 py-2.5">{{ $b['jenis'] }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap">{{ $ukuran($b['ukuran']) }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap">{{ $b['waktu']->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5">
                                <div class="flex items-center gap-4">
                                    <a href="{{ route('backup.unduh', $b['nama']) }}" class="underline underline-offset-2">Unduh</a>
                                    <a href="{{ route('backup.konfirmasi', $b['nama']) }}" class="underline underline-offset-2">Pulihkan</a>
                                    <form method="POST" action="{{ route('backup.destroy', $b['nama']) }}"
                                          onsubmit="return confirm('Hapus backup {{ $b['nama'] }} dari server?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-danger underline underline-offset-2">Hapus</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <h2 class="mt-8 text-lg font-semibold">Pulihkan dari berkas di komputer</h2>
    <form method="POST" action="{{ route('backup.unggah') }}" enctype="multipart/form-data"
          class="mt-3 max-w-xl rounded-md border border-line bg-white p-5">
        @csrf
        <label for="berkas_backup" class="block text-sm font-medium">Berkas backup (.zip)</label>
        <input id="berkas_backup" name="berkas_backup" type="file" accept=".zip" required class="{{ $input }}">
        <p class="mt-1 text-xs text-ink/60">Berkas diperiksa dulu. Data belum diubah sampai Anda mengonfirmasi di halaman berikutnya.</p>
        @error('berkas_backup') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        <button type="submit"
                class="mt-4 rounded-md border border-line bg-white px-4 py-2 text-sm hover:bg-concrete focus-visible:outline-2 focus-visible:outline-primary">
            Unggah dan periksa
        </button>
    </form>

    <h2 class="mt-8 text-lg font-semibold">Riwayat terakhir</h2>
    <div class="mt-3 overflow-x-auto rounded-md border border-line bg-white">
        @if ($riwayat->isEmpty())
            <p class="px-6 py-8 text-center text-sm text-ink/60">Belum ada riwayat.</p>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-concrete">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Waktu</th>
                        <th class="px-4 py-2.5 font-medium">Aksi</th>
                        <th class="px-4 py-2.5 font-medium">Berkas</th>
                        <th class="px-4 py-2.5 font-medium">Oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($riwayat as $r)
                        <tr class="border-b border-line last:border-0">
                            <td class="px-4 py-2.5 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->waktu)->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-2.5">{{ $r->jenis === 'restore' ? 'Restore' : 'Backup' }}</td>
                            <td class="px-4 py-2.5">{{ $r->file_path }}</td>
                            <td class="px-4 py-2.5">{{ $r->nama }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
