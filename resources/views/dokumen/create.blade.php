@extends('layouts.app')

@section('title', 'Tambah dokumen')

@section('content')
    <h1 class="text-xl font-semibold text-primary">Tambah dokumen</h1>

    <form method="POST" action="{{ route('dokumen.store') }}" enctype="multipart/form-data" class="mt-6 max-w-xl">
        @csrf
        @include('dokumen._form')

        <div class="mt-6 flex items-center gap-4">
            <button type="submit"
                    class="rounded-md bg-accent px-4 py-2 text-sm font-semibold text-primary hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Simpan dokumen
            </button>
            <a href="{{ route('dokumen.index') }}" class="text-sm underline underline-offset-2">Batal</a>
        </div>
    </form>
@endsection
