@extends('layouts.app')

@section('title', 'Ubah akun')

@section('content')
    <h1 class="text-2xl font-semibold">Ubah akun {{ $user->username }}</h1>

    <form method="POST" action="{{ route('akun.update', $user) }}" class="mt-6 max-w-md">
        @csrf
        @method('PUT')
        @include('akun._form')

        <div class="mt-6 flex items-center gap-4">
            <button type="submit"
                    class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
                Simpan perubahan
            </button>
            <a href="{{ route('akun.index') }}" class="text-sm underline underline-offset-2">Batal</a>
        </div>
    </form>
@endsection
