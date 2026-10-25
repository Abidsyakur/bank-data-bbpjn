@extends('layouts.app')

@section('title', 'Kelola dokumen')

@section('content')
    @php
        $tampilStatus = ! $layanan || $layanan->butuh_tindak_lanjut;
        $tautan = fn (?string $s) => route('dokumen.index', array_filter([
            'layanan' => $layanan?->kode,
            'q'       => $kata !== '' ? $kata : null,
            'status'  => $s,
        ]));
        $tab = [
            ['', 'Semua', $hitung['semua'], null],
            ['menunggu', 'Menunggu', $hitung['menunggu'], 'bg-alert'],
            ['selesai', 'Selesai', $hitung['selesai'], 'bg-ok'],
        ];
        $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
    @endphp

    <div class="flex flex-wrap items-start justify-between gap-3">
        <h1 class="text-xl font-semibold">Kelola Dokumen{{ $layanan ? ' - ' . $layanan->nama : '' }}</h1>
        @can('create', \App\Models\Dokumen::class)
            <a href="{{ route('dokumen.create', array_filter(['layanan' => $layanan?->kode])) }}"
               class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Tambah dokumen
            </a>
        @endcan
    </div>

    <form method="GET" action="{{ route('dokumen.index') }}" role="search"
          class="mt-5 grid gap-3 rounded-md border border-line bg-white p-4 sm:grid-cols-[1fr_16rem_auto] sm:items-end">
        @if ($status)
            <input type="hidden" name="status" value="{{ $status }}">
        @endif
        <div>
            <label for="q" class="block text-xs font-medium text-ink/70">Nomor surat atau judul</label>
            <input id="q" name="q" type="text" value="{{ $kata }}" class="{{ $input }}">
        </div>
        <div>
            <label for="layanan" class="block text-xs font-medium text-ink/70">Jenis layanan</label>
            <select id="layanan" name="layanan" class="{{ $input }}">
                <option value="">Semua layanan</option>
                @foreach ($layananList as $l)
                    <option value="{{ $l->kode }}" @selected($layanan?->kode === $l->kode)>{{ $l->nama }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-4">
            <button type="submit"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Cari
            </button>
            @if ($kata !== '' || $layanan || $status)
                <a href="{{ route('dokumen.index') }}" class="text-sm underline underline-offset-2">Atur ulang</a>
            @endif
        </div>
    </form>

    @if ($tampilStatus)
        <nav class="mt-5 flex gap-6 border-b border-line text-sm" aria-label="Filter status">
            @foreach ($tab as [$kunci, $label, $jumlah, $titik])
                <a href="{{ $tautan($kunci ?: null) }}"
                   class="-mb-px flex items-center gap-2 border-b-2 px-1 pb-2.5 {{ ($status ?? '') === $kunci ? 'border-primary font-medium' : 'border-transparent text-ink/60 hover:text-ink' }}">
                    @if ($titik)
                        <span class="size-2.5 rounded-full {{ $titik }}"></span>
                    @endif
                    {{ $label }}
                    <span class="text-ink/50">{{ $jumlah }}</span>
                </a>
            @endforeach
        </nav>
    @endif

    <div class="mt-5 overflow-x-auto rounded-md border border-line bg-white">
        @if ($dokumen->isEmpty())
            <div class="px-6 py-16 text-center">
                @if ($kata !== '')
                    <p class="font-medium">Tidak ada dokumen yang cocok dengan "{{ $kata }}".</p>
                    <p class="mt-1 text-sm text-ink/60">Coba kata kunci lain atau atur ulang filter.</p>
                @elseif ($status === 'menunggu')
                    <p class="font-medium">Tidak ada dokumen yang menunggu.</p>
                    <p class="mt-1 text-sm text-ink/60">Terima kasih atas kerja kerasnya.</p>
                @else
                    <p class="font-medium">Belum ada dokumen.</p>
                    @can('create', \App\Models\Dokumen::class)
                        <p class="mt-1 text-sm text-ink/60">Mulai dengan menambahkan dokumen pertama.</p>
                    @endcan
                @endif
            </div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-concrete">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Nomor surat</th>
                        <th class="px-4 py-2.5 font-medium">Judul</th>
                        @unless ($layanan)
                            <th class="px-4 py-2.5 font-medium">Layanan</th>
                        @endunless
                        <th class="px-4 py-2.5 font-medium">Tanggal</th>
                        <th class="px-4 py-2.5 font-medium">Status</th>
                        <th class="px-4 py-2.5 font-medium">Diunggah oleh</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($dokumen as $d)
                        <tr class="border-b border-line last:border-0 hover:bg-concrete/60">
                            <td class="px-4 py-2.5">{{ $d->nomor_surat ?: '-' }}</td>
                            <td class="px-4 py-2.5">
                                <a href="{{ route('dokumen.show', $d) }}" class="font-medium underline-offset-2 hover:underline">{{ $d->judul }}</a>
                            </td>
                            @unless ($layanan)
                                <td class="px-4 py-2.5">{{ $d->layanan->nama }}</td>
                            @endunless
                            <td class="px-4 py-2.5 whitespace-nowrap">{{ $d->tanggal->format('d/m/Y') }}</td>
                            <td class="px-4 py-2.5">
                                @if ($d->status)
                                    <span class="inline-flex items-center gap-2">
                                        <span class="size-2.5 rounded-full {{ $d->status === 'menunggu' ? 'bg-alert' : 'bg-ok' }}"></span>
                                        {{ $d->labelStatus() }}
                                    </span>
                                @else
                                    <span class="text-ink/50">-</span>
                                @endif
                            </td>
                            <td class="px-4 py-2.5">{{ $d->user->nama }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">{{ $dokumen->links() }}</div>
@endsection
