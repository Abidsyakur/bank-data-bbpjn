@extends('layouts.app')

@section('title', $dokumen->judul)

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
        $perluTindakLanjut = $dokumen->layanan->butuh_tindak_lanjut;
    @endphp

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

    @if ($perluTindakLanjut)
        <h2 class="mt-8 text-lg font-semibold">Tindak lanjut</h2>

        <div class="mt-3 max-w-2xl space-y-3">
            @forelse ($tindakLanjut as $t)
                <article class="rounded-md border border-line bg-white p-4 text-sm">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-medium">{{ $t->tanggal->format('d/m/Y') }} - {{ $t->user->nama }}</p>
                        @can('update', $dokumen)
                            <form method="POST" action="{{ route('tindak-lanjut.destroy', $t) }}"
                                  onsubmit="return confirm('Hapus catatan tindak lanjut ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-danger underline underline-offset-2">Hapus</button>
                            </form>
                        @endcan
                    </div>
                    <p class="mt-2 whitespace-pre-line">{{ $t->catatan }}</p>
                    @if ($t->bukti_path)
                        <div class="mt-3 flex items-center gap-4">
                            <a href="{{ route('tindak-lanjut.bukti', $t) }}" target="_blank" rel="noopener" class="underline underline-offset-2">Lihat bukti</a>
                            <a href="{{ route('tindak-lanjut.bukti', [$t, 'unduh' => 1]) }}" class="underline underline-offset-2">Unduh bukti</a>
                        </div>
                    @endif
                </article>
            @empty
                <p class="text-sm text-ink/60">Belum ada tindak lanjut.</p>
            @endforelse
        </div>

        @can('update', $dokumen)
            @if ($dokumen->status === 'selesai')
                <form method="POST" action="{{ route('tindak-lanjut.buka-kembali', $dokumen) }}"
                      class="mt-4 flex max-w-2xl flex-wrap items-center justify-between gap-3 rounded-md border border-ok/30 bg-ok/10 px-4 py-3 text-sm"
                      onsubmit="return confirm('Buka kembali dokumen ini? Statusnya akan kembali menjadi Menunggu.')">
                    @csrf
                    @method('PATCH')
                    <span class="text-ok">Dokumen ini sudah selesai.</span>
                    <button type="submit" class="rounded-md border border-ok/40 px-3 py-1.5 text-ok hover:bg-ok/10 focus-visible:outline-2 focus-visible:outline-ok">
                        Buka kembali
                    </button>
                </form>
            @else
                <form method="POST" action="{{ route('tindak-lanjut.store', $dokumen) }}" enctype="multipart/form-data"
                      class="mt-4 max-w-2xl rounded-md border border-line bg-white p-5">
                    @csrf
                    <h3 class="font-semibold">Tambah tindak lanjut</h3>

                    <label for="tanggal" class="mt-4 block text-sm font-medium">Tanggal</label>
                    <input id="tanggal" name="tanggal" type="date" required value="{{ old('tanggal', now()->format('Y-m-d')) }}" class="{{ $input }} sm:w-56">
                    @error('tanggal') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                    <label for="catatan" class="mt-4 block text-sm font-medium">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="4" required class="{{ $input }}">{{ old('catatan') }}</textarea>
                    @error('catatan') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                    <label for="bukti" class="mt-4 block text-sm font-medium">Bukti (opsional)</label>
                    <input id="bukti" name="bukti" type="file" accept=".pdf,.jpg,.jpeg,.png" class="{{ $input }}">
                    <p class="mt-1 text-xs text-ink/60">PDF, JPG, atau PNG. Maksimal 10 MB.</p>
                    @error('bukti') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

                    <label class="mt-4 flex items-center gap-2 text-sm">
                        <input type="checkbox" name="selesai" value="1" class="size-4 accent-primary" @checked(old('selesai'))>
                        Tandai dokumen ini selesai
                    </label>

                    <button type="submit"
                            class="mt-5 rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                        Simpan tindak lanjut
                    </button>
                </form>
            @endif
        @endcan
    @endif

    <p class="mt-6"><a href="{{ route('dokumen.index', ['layanan' => $dokumen->layanan->kode]) }}" class="text-sm underline underline-offset-2">Kembali ke daftar</a></p>
@endsection
