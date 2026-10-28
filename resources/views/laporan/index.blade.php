@extends('layouts.app')

@section('title', 'Laporan rekapitulasi')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
        $parameter = array_filter(['dari' => $filter['dari'], 'sampai' => $filter['sampai'], 'layanan' => $filter['layanan']]);
        $r = $laporan['ringkasan'];
    @endphp

    <h1 class="text-xl font-semibold text-primary">Laporan rekapitulasi</h1>
    <p class="mt-1 text-sm text-ink/60">Gabungan arsip PPID, SP4N-LAPOR!, dan JAKI. Atur periode dan kategori, lalu cetak atau ekspor.</p>

    <form method="GET" action="{{ route('laporan.index') }}"
          class="mt-6 grid gap-3 rounded-md border border-line bg-white p-4 sm:grid-cols-[11rem_11rem_1fr_auto] sm:items-end">
        <div>
            <label for="dari" class="block text-xs font-medium text-ink/70">Dari tanggal</label>
            <input id="dari" name="dari" type="date" value="{{ $filter['dari'] }}" class="{{ $input }}">
            @error('dari') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="sampai" class="block text-xs font-medium text-ink/70">Sampai tanggal</label>
            <input id="sampai" name="sampai" type="date" value="{{ $filter['sampai'] }}" class="{{ $input }}">
            @error('sampai') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="layanan" class="block text-xs font-medium text-ink/70">Kategori (layanan)</label>
            <select id="layanan" name="layanan" class="{{ $input }}">
                <option value="">Semua layanan</option>
                @foreach ($layananList as $l)
                    <option value="{{ $l->kode }}" @selected($filter['layanan'] === $l->kode)>{{ $l->nama }}</option>
                @endforeach
            </select>
            @error('layanan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
        <button type="submit"
                class="rounded-md bg-accent px-4 py-2 text-sm font-semibold text-primary hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
            Tampilkan
        </button>
    </form>
    <p class="mt-2 text-xs text-ink/60">Kosongkan kedua tanggal untuk mencakup semua tanggal.</p>

    <div class="mt-6 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h2 class="text-lg font-semibold">{{ $laporan['kategori'] }}</h2>
            <p class="text-sm text-ink/60">Periode: {{ $laporan['periode'] }}</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="{{ route('laporan.excel', $parameter) }}"
               class="rounded-md border border-line bg-white px-4 py-2 text-sm hover:bg-concrete focus-visible:outline-2 focus-visible:outline-primary">
                Unduh Excel
            </a>
            <a href="{{ route('laporan.pdf', $parameter) }}"
               class="rounded-md bg-accent px-4 py-2 text-sm font-semibold text-primary hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Unduh PDF
            </a>
        </div>
    </div>

    <div class="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @foreach ([['Total dokumen', $r['total']], ['Total pengaduan', $r['pengaduan']], ['Menunggu', $r['menunggu']], ['Selesai', $r['selesai']]] as [$label, $nilai])
            <div class="rounded-md border border-line bg-white p-4">
                <p class="text-sm text-ink/70">{{ $label }}</p>
                <p class="mt-1 text-2xl font-semibold">{{ number_format($nilai, 0, ',', '.') }}</p>
            </div>
        @endforeach
    </div>

    <div class="mt-4 overflow-x-auto rounded-md border border-line bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-concrete">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Layanan</th>
                    <th class="px-4 py-2.5 font-medium">Jenis</th>
                    <th class="px-4 py-2.5 text-right font-medium">Total</th>
                    <th class="px-4 py-2.5 text-right font-medium">Menunggu</th>
                    <th class="px-4 py-2.5 text-right font-medium">Selesai</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($laporan['rekap'] as $l)
                    <tr class="border-b border-line">
                        <td class="px-4 py-2.5">{{ $l->nama }}</td>
                        <td class="px-4 py-2.5">{{ $l->is_pengaduan ? 'Pengaduan' : 'Arsip' }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $l->total }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $l->butuh_tindak_lanjut ? $l->menunggu : '-' }}</td>
                        <td class="px-4 py-2.5 text-right">{{ $l->butuh_tindak_lanjut ? $l->selesai : '-' }}</td>
                    </tr>
                @endforeach
                <tr class="bg-concrete font-semibold">
                    <td class="px-4 py-2.5" colspan="2">Jumlah</td>
                    <td class="px-4 py-2.5 text-right">{{ $r['total'] }}</td>
                    <td class="px-4 py-2.5 text-right">{{ $r['menunggu'] }}</td>
                    <td class="px-4 py-2.5 text-right">{{ $r['selesai'] }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    <p class="mt-3 text-sm text-ink/60">
        Berkas PDF dan Excel memuat rekap di atas beserta rincian {{ number_format($laporan['dokumen']->count(), 0, ',', '.') }} dokumen.
    </p>
@endsection
