@php
    $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
    $edit = isset($dokumen);
@endphp

<label for="layanan_id" class="block text-sm font-medium">Layanan</label>
<select id="layanan_id" name="layanan_id" required class="{{ $input }}">
    <option value="">Pilih layanan</option>
    @foreach ($layananList as $l)
        <option value="{{ $l->id }}" @selected(old('layanan_id', $dokumen->layanan_id ?? $terpilih ?? null) == $l->id)>{{ $l->nama }}</option>
    @endforeach
</select>
@error('layanan_id') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="nomor_surat" class="mt-4 block text-sm font-medium">Nomor surat</label>
<input id="nomor_surat" name="nomor_surat" type="text" value="{{ old('nomor_surat', $dokumen->nomor_surat ?? '') }}" class="{{ $input }}">
<p class="mt-1 text-xs text-ink/60">Kosongkan jika dokumen tidak memiliki nomor surat.</p>
@error('nomor_surat') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="judul" class="mt-4 block text-sm font-medium">Judul</label>
<input id="judul" name="judul" type="text" required value="{{ old('judul', $dokumen->judul ?? '') }}" class="{{ $input }}">
@error('judul') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="tanggal" class="mt-4 block text-sm font-medium">Tanggal</label>
<input id="tanggal" name="tanggal" type="date" required value="{{ old('tanggal', isset($dokumen) ? $dokumen->tanggal->format('Y-m-d') : now()->format('Y-m-d')) }}" class="{{ $input }}">
@error('tanggal') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="berkas" class="mt-4 block text-sm font-medium">Berkas</label>
@if ($edit && $adaBerkas)
    <p class="mt-1 text-sm">
        Berkas saat ini:
        <a href="{{ route('dokumen.berkas', $dokumen) }}" target="_blank" rel="noopener" class="underline underline-offset-2">buka</a>.
        Pilih berkas baru hanya jika ingin menggantinya.
    </p>
@endif
<input id="berkas" name="berkas" type="file" accept=".pdf,.jpg,.jpeg,.png" class="{{ $input }}">
<p class="mt-1 text-xs text-ink/60">PDF, JPG, atau PNG. Maksimal 10 MB.</p>
@error('berkas') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror
