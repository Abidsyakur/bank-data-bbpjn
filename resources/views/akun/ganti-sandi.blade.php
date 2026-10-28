@extends('layouts.app')

@section('title', 'Ganti kata sandi')

@section('content')
    @php
        $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
    @endphp

    <h1 class="text-2xl font-semibold text-primary">Ganti kata sandi</h1>

    <form method="POST" action="{{ route('sandi.update') }}" class="mt-6 max-w-md">
        @csrf
        @method('PUT')

        <label for="current_password" class="block text-sm font-medium">Kata sandi saat ini</label>
        <input id="current_password" name="current_password" type="password" required autocomplete="current-password" class="{{ $input }}">
        @error('current_password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

        <label for="password" class="mt-4 block text-sm font-medium">Kata sandi baru</label>
        <input id="password" name="password" type="password" required autocomplete="new-password" class="{{ $input }}">
        <p class="mt-1 text-xs text-ink/60">Minimal 8 karakter.</p>
        @error('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

        <label for="password_confirmation" class="mt-4 block text-sm font-medium">Ulangi kata sandi baru</label>
        <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="{{ $input }}">

        <button type="submit"
                class="mt-6 rounded-md bg-accent px-4 py-2 text-sm font-semibold text-primary hover:bg-accent-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
            Simpan kata sandi
        </button>
    </form>
@endsection
