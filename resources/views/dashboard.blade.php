@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
    @php
        $kartu = [
            ['Total dokumen', $ringkasan['total'], route('dokumen.index'), null, null],
            ['Total pengaduan', $ringkasan['pengaduan'], null, null,
                $ringkasan['pengaduan_berstatus'] ? $ringkasan['pengaduan_menunggu'] . ' masih menunggu' : null],
        ];
        if ($ringkasan['ada_status']) {
            $kartu[] = ['Menunggu tindak lanjut', $ringkasan['menunggu'], route('dokumen.index', ['status' => 'menunggu']), 'bg-alert', null];
            $kartu[] = ['Selesai', $ringkasan['selesai'], route('dokumen.index', ['status' => 'selesai']), 'bg-ok', null];
        }
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h1 class="text-xl font-semibold">Dashboard</h1>
            <p class="mt-1 text-sm text-ink/60">
                {{ $user->isPimpinan() ? 'Rekapitulasi seluruh layanan' : 'Rekapitulasi layanan yang Anda kelola' }}
            </p>
        </div>
        @if ($user->isPimpinan())
            <a href="{{ route('laporan.index') }}"
               class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Cetak laporan
            </a>
        @endif
    </div>

    <div class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ($kartu as [$label, $nilai, $url, $titik, $catatan])
            @php
                $kelas = 'block rounded-md border border-line bg-white p-5';
            @endphp
            @if ($url)
                <a href="{{ $url }}" class="{{ $kelas }} hover:border-ink/40 focus-visible:outline-2 focus-visible:outline-primary">
                    @include('partials.kartu-isi')
                </a>
            @else
                <div class="{{ $kelas }}">
                    @include('partials.kartu-isi')
                </div>
            @endif
        @endforeach
    </div>

    <section class="mt-6 rounded-md border border-line bg-white p-5" aria-labelledby="judul-bulan">
        <h2 id="judul-bulan" class="font-semibold">Dokumen per bulan</h2>
        <p class="text-xs text-ink/60">12 bulan terakhir, berdasarkan tanggal dokumen</p>
        <div class="mt-4" style="height: 260px">
            <canvas data-config='@json($chartBulan)' role="img" aria-label="Grafik jumlah dokumen per bulan"></canvas>
        </div>
    </section>

    {{-- Layanan yang melacak status tindak lanjut (aduan/laporan) --}}
    @if ($berstatus->isNotEmpty())
        <section class="mt-8" aria-labelledby="judul-status">
            <h2 id="judul-status" class="text-lg font-semibold">Pengaduan dengan tindak lanjut</h2>
            <p class="text-sm text-ink/60">Hanya layanan ini yang dipantau status Menunggu dan Selesai.</p>

            <div class="mt-3 grid gap-4 xl:grid-cols-2">
                <div class="rounded-md border border-line bg-white p-5">
                    <div style="height: {{ $tinggiStatus }}px">
                        <canvas data-config='@json($chartStatus)' role="img" aria-label="Grafik status tindak lanjut per layanan"></canvas>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-md border border-line bg-white">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-line bg-concrete">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">Layanan</th>
                                <th class="px-4 py-2.5 text-right font-medium">Total</th>
                                <th class="px-4 py-2.5 text-right font-medium">Menunggu</th>
                                <th class="px-4 py-2.5 text-right font-medium">Selesai</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($berstatus as $l)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('dokumen.index', ['layanan' => $l->kode]) }}" class="underline-offset-2 hover:underline">{{ $l->nama }}</a>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">{{ $l->total }}</td>
                                    <td class="px-4 py-2.5 text-right">{{ $l->menunggu }}</td>
                                    <td class="px-4 py-2.5 text-right">{{ $l->selesai }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif

    {{-- Layanan arsip: hanya jumlah dokumen, tanpa status --}}
    @if ($arsip->isNotEmpty())
        <section class="mt-8" aria-labelledby="judul-arsip">
            <h2 id="judul-arsip" class="text-lg font-semibold">Arsip dokumen</h2>
            <p class="text-sm text-ink/60">Layanan ini hanya diarsipkan, tidak memiliki status tindak lanjut.</p>

            <div class="mt-3 grid gap-4 xl:grid-cols-2">
                <div class="rounded-md border border-line bg-white p-5">
                    <div style="height: {{ $tinggiArsip }}px">
                        <canvas data-config='@json($chartArsip)' role="img" aria-label="Grafik jumlah dokumen arsip per layanan"></canvas>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-md border border-line bg-white">
                    <table class="w-full text-left text-sm">
                        <thead class="border-b border-line bg-concrete">
                            <tr>
                                <th class="px-4 py-2.5 font-medium">Layanan</th>
                                <th class="px-4 py-2.5 text-right font-medium">Jumlah dokumen</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($arsip as $l)
                                <tr class="border-b border-line last:border-0">
                                    <td class="px-4 py-2.5">
                                        <a href="{{ route('dokumen.index', ['layanan' => $l->kode]) }}" class="underline-offset-2 hover:underline">{{ $l->nama }}</a>
                                    </td>
                                    <td class="px-4 py-2.5 text-right">{{ $l->total }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    @endif
@endsection
