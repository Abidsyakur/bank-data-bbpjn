@extends('layouts.app')

@section('title', 'Ubah dokumen')

@section('content')
    <h1 class="text-xl font-semibold">Ubah dokumen</h1>

    <form method="POST" action="{{ route('dokumen.update', $dokumen) }}" enctype="multipart/form-data" class="mt-6 max-w-xl">
        @csrf
        @method('PUT')
        @include('dokumen._form')

        <div class="mt-6 flex items-center gap-4">
            <button type="submit"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Simpan perubahan
            </button>
            <a href="{{ route('dokumen.show', $dokumen) }}" class="text-sm underline underline-offset-2">Batal</a>
        </div>
    </form>
@endsection
