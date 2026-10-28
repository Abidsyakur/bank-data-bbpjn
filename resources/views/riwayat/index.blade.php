@extends('layouts.app')

@section('title', 'Riwayat aktivitas')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
        $adaFilter = collect($filter)->filter()->isNotEmpty();
    @endphp

    <h1 class="text-xl font-semibold text-primary">Riwayat aktivitas</h1>
    <p class="mt-1 max-w-2xl text-sm text-ink/60">
        Catatan otomatis siapa melakukan apa dan kapan. Data terbaru berada di atas dan tidak dapat diubah dari aplikasi.
    </p>

    <form method="GET" action="{{ route('riwayat.index') }}" role="search"
          class="mt-5 grid gap-3 rounded-md border border-line bg-white p-4 sm:grid-cols-2 xl:grid-cols-[1fr_1fr_10rem_10rem_auto] xl:items-end">
        <div>
            <label for="pengguna" class="block text-xs font-medium text-ink/70">Pengguna</label>
            <select id="pengguna" name="pengguna" class="{{ $input }}">
                <option value="">Semua pengguna</option>
                @foreach ($pengguna as $p)
                    <option value="{{ $p->id }}" @selected((string) ($filter['pengguna'] ?? '') === (string) $p->id)>{{ $p->nama }} ({{ $p->username }})</option>
                @endforeach
            </select>
            @error('pengguna') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="aksi" class="block text-xs font-medium text-ink/70">Jenis aktivitas</label>
            <select id="aksi" name="aksi" class="{{ $input }}">
                <option value="">Semua aktivitas</option>
                @foreach ($aksiList as $kode => $label)
                    <option value="{{ $kode }}" @selected(($filter['aksi'] ?? '') === $kode)>{{ $label }}</option>
                @endforeach
            </select>
            @error('aksi') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="dari" class="block text-xs font-medium text-ink/70">Dari tanggal</label>
            <input id="dari" name="dari" type="date" value="{{ $filter['dari'] ?? '' }}" class="{{ $input }}">
            @error('dari') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="sampai" class="block text-xs font-medium text-ink/70">Sampai tanggal</label>
            <input id="sampai" name="sampai" type="date" value="{{ $filter['sampai'] ?? '' }}" class="{{ $input }}">
            @error('sampai') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
        </div>
        <div class="flex items-center gap-4 sm:col-span-2 xl:col-span-1">
            <button type="submit"
                    class="rounded-md bg-accent px-4 py-2 text-sm font-semibold text-primary hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Terapkan
            </button>
            @if ($adaFilter)
                <a href="{{ route('riwayat.index') }}" class="text-sm underline underline-offset-2">Atur ulang</a>
            @endif
        </div>
    </form>

    <div class="mt-5 overflow-x-auto rounded-md border border-line bg-white">
        @if ($log->isEmpty())
            <div class="px-6 py-16 text-center">
                <p class="font-medium">{{ $adaFilter ? 'Tidak ada aktivitas yang cocok dengan filter.' : 'Belum ada aktivitas tercatat.' }}</p>
                @if ($adaFilter)
                    <p class="mt-1 text-sm text-ink/60">Coba ubah atau atur ulang filter.</p>
                @endif
            </div>
        @else
            <table class="w-full text-left text-sm">
                <thead class="border-b border-line bg-concrete">
                    <tr>
                        <th class="px-4 py-2.5 font-medium">Waktu</th>
                        <th class="px-4 py-2.5 font-medium">Pengguna</th>
                        <th class="px-4 py-2.5 font-medium">Aktivitas</th>
                        <th class="px-4 py-2.5 font-medium">Keterangan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($log as $row)
                        <tr class="border-b border-line align-top last:border-0">
                            <td class="px-4 py-2.5 whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($row->waktu)->format('d/m/Y H:i:s') }}</td>
                            <td class="px-4 py-2.5">
                                <span class="font-medium">{{ $row->nama }}</span>
                                <span class="block text-xs text-ink/60">{{ $row->username }}</span>
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap {{ \App\Support\LabelAksi::penting($row->aksi) ? 'font-medium text-danger' : '' }}">
                                {{ \App\Support\LabelAksi::label($row->aksi) }}
                            </td>
                            <td class="px-4 py-2.5">
                                @if ($row->keterangan)
                                    <span>{{ $row->keterangan }}</span>
                                @endif
                                @if ($row->dokumen_id)
                                    <span class="block text-xs">
                                        @if ($row->dokumen_dihapus)
                                            <span class="text-ink/50">Dokumen: {{ $row->dokumen_judul }} (telah dihapus)</span>
                                        @else
                                            Dokumen:
                                            <a href="{{ route('dokumen.show', $row->dokumen_id) }}" class="underline underline-offset-2">{{ $row->dokumen_judul }}</a>
                                        @endif
                                    </span>
                                @endif
                                @if (! $row->keterangan && ! $row->dokumen_id)
                                    <span class="text-ink/40">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    <div class="mt-4">{{ $log->links() }}</div>
@endsection
