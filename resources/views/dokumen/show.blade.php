@extends('layouts.app')

@section('title', $dokumen->judul)

@section('content')
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <p class="text-sm text-ink/60">{{ $dokumen->layanan->nama }}</p>
            <h1 class="text-xl font-semibold">{{ $dokumen->judul }}</h1>
        </div>
        <div class="flex items-center gap-3">
            @can('update', $dokumen)
                <a href="{{ route('dokumen.edit', $dokumen) }}"
                   class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                    Ubah
                </a>
            @endcan
            @can('delete', $dokumen)
                <form method="POST" action="{{ route('dokumen.destroy', $dokumen) }}"
                      onsubmit="return confirm('Hapus dokumen ini? Dokumen tidak akan tampil lagi di daftar.')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="rounded-md border border-danger/40 px-4 py-2 text-sm text-danger hover:bg-danger/10 focus-visible:outline-2 focus-visible:outline-danger">
                        Hapus
                    </button>
                </form>
            @endcan
        </div>
    </div>

    <dl class="mt-6 grid max-w-2xl gap-x-6 gap-y-4 rounded-md border border-line bg-white p-5 text-sm sm:grid-cols-2">
        <div>
            <dt class="text-ink/60">Nomor surat</dt>
            <dd class="mt-0.5 font-medium">{{ $dokumen->nomor_surat ?: '-' }}</dd>
        </div>
        <div>
            <dt class="text-ink/60">Tanggal</dt>
            <dd class="mt-0.5 font-medium">{{ $dokumen->tanggal->format('d/m/Y') }}</dd>
        </div>
        <div>
            <dt class="text-ink/60">Status tindak lanjut</dt>
            <dd class="mt-0.5 font-medium">
                @if ($dokumen->status)
                    <span class="inline-flex items-center gap-2">
                        <span class="size-2.5 rounded-full {{ $dokumen->status === 'menunggu' ? 'bg-alert' : 'bg-ok' }}"></span>
                        {{ $dokumen->labelStatus() }}
                    </span>
                @else
                    Tidak berlaku
                @endif
            </dd>
        </div>
        <div>
            <dt class="text-ink/60">Diunggah oleh</dt>
            <dd class="mt-0.5 font-medium">{{ $dokumen->user->nama }}</dd>
        </div>
    </dl>

    <h2 class="mt-8 text-lg font-semibold">Berkas</h2>
    <div class="mt-3 max-w-2xl rounded-md border border-line bg-white p-5 text-sm">
        @if ($adaBerkas)
            <div class="flex items-center gap-3">
                <a href="{{ route('dokumen.berkas', $dokumen) }}" target="_blank" rel="noopener"
                   class="rounded-md border border-line px-4 py-2 hover:bg-concrete focus-visible:outline-2 focus-visible:outline-primary">
                    Buka berkas
                </a>
                <a href="{{ route('dokumen.berkas', [$dokumen, 'unduh' => 1]) }}"
                   class="rounded-md bg-primary px-4 py-2 font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                    Unduh
                </a>
            </div>
        @else
            <p class="text-ink/60">Dokumen ini belum memiliki berkas.</p>
        @endif
    </div>

    <p class="mt-6"><a href="{{ route('dokumen.index', ['layanan' => $dokumen->layanan->kode]) }}" class="text-sm underline underline-offset-2">Kembali ke daftar</a></p>
@endsection
