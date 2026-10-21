@extends('layouts.app')

@section('title', 'Manajemen akun')

@section('content')
    <div class="flex flex-wrap items-center justify-between gap-3">
        <h1 class="text-2xl font-semibold">Manajemen akun</h1>
        <a href="{{ route('akun.create') }}"
           class="rounded-md bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-ink">
            Tambah akun
        </a>
    </div>

    <div class="mt-5 overflow-x-auto rounded-md border border-line bg-white">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-line bg-concrete">
                <tr>
                    <th class="px-4 py-2.5 font-medium">Nama</th>
                    <th class="px-4 py-2.5 font-medium">Username</th>
                    <th class="px-4 py-2.5 font-medium">Peran</th>
                    <th class="px-4 py-2.5 font-medium">Status</th>
                    <th class="px-4 py-2.5 font-medium">Tindakan</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $item)
                    <tr class="border-b border-line last:border-0">
                        <td class="px-4 py-2.5">{{ $item->nama }}</td>
                        <td class="px-4 py-2.5">{{ $item->username }}</td>
                        <td class="px-4 py-2.5">{{ $item->role->nama }}</td>
                        <td class="px-4 py-2.5">
                            <span class="{{ $item->aktif ? 'text-ok' : 'text-danger' }} font-medium">
                                {{ $item->aktif ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('akun.edit', $item) }}" class="underline underline-offset-2">Ubah</a>
                                @if ($item->id !== auth()->id())
                                    <form method="POST" action="{{ route('akun.toggle', $item) }}"
                                          onsubmit="return confirm('{{ $item->aktif ? 'Nonaktifkan' : 'Aktifkan' }} akun {{ $item->username }}?')">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="underline underline-offset-2">
                                            {{ $item->aktif ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endsection
