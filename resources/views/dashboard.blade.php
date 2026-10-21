@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    <h1 class="text-2xl font-semibold">Selamat datang, {{ $user->nama }}</h1>
    <p class="mt-1 text-sm text-ink/70">Anda masuk sebagai {{ $user->role->nama }}.</p>

    <h2 class="mt-8 text-lg font-semibold">
        {{ $user->isPimpinan() ? 'Seluruh layanan' : 'Layanan yang Anda kelola' }}
    </h2>

    <div class="mt-3 overflow-x-auto rounded-md border border-line bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-concrete">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Layanan</th>
                    <th class="px-4 py-2.5 font-medium">Jenis</th>
                    <th class="px-4 py-2.5 font-medium">Tindak lanjut</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($layanan as $item)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-2.5">{{ $item->nama }}</td>
                        <td class="px-4 py-2.5">{{ $item->is_pengaduan ? 'Pengaduan' : 'Arsip' }}</td>
                        <td class="px-4 py-2.5">{{ $item->butuh_tindak_lanjut ? 'Dipantau' : 'Tidak ada' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <p class="mt-4 text-sm text-ink/70">
        Jumlah dokumen dan pengaduan akan tampil di halaman ini setelah modul arsip selesai.
    </p>
@endsection
