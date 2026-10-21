@php
    $input = 'mt-1 w-full rounded-md border border-line bg-white px-3 py-2 text-sm focus:border-ink focus:outline-2 focus:outline-primary';
    $edit  = isset($user);
    $self  = $edit && $user->id === auth()->id();
@endphp

<label for="nama" class="block text-sm font-medium">Nama</label>
<input id="nama" name="nama" type="text" required value="{{ old('nama', $user->nama ?? '') }}" class="{{ $input }}">
@error('nama') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="username" class="mt-4 block text-sm font-medium">Username</label>
<input id="username" name="username" type="text" required value="{{ old('username', $user->username ?? '') }}" class="{{ $input }}">
@error('username') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="role_id" class="mt-4 block text-sm font-medium">Peran</label>
<select id="role_id" name="role_id" required @disabled($self) class="{{ $input }}">
    @foreach ($roles as $role)
        <option value="{{ $role->id }}" @selected(old('role_id', $user->role_id ?? null) == $role->id)>{{ $role->nama }}</option>
    @endforeach
</select>
@if ($self)
    <p class="mt-1 text-xs text-ink/60">Peran akun Anda sendiri tidak dapat diubah.</p>
@endif
@error('role_id') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="password" class="mt-4 block text-sm font-medium">{{ $edit ? 'Kata sandi baru' : 'Kata sandi' }}</label>
<input id="password" name="password" type="password" autocomplete="new-password" @required(! $edit) class="{{ $input }}">
<p class="mt-1 text-xs text-ink/60">{{ $edit ? 'Kosongkan jika tidak ingin mengubah. ' : '' }}Minimal 8 karakter.</p>
@error('password') <p class="mt-1 text-sm text-danger">{{ $message }}</p> @enderror

<label for="password_confirmation" class="mt-4 block text-sm font-medium">Ulangi kata sandi</label>
<input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" @required(! $edit) class="{{ $input }}">

@if ($edit)
    <label class="mt-4 flex items-center gap-2 text-sm">
        <input type="checkbox" name="aktif" value="1" class="size-4 accent-primary"
               @checked(old('aktif', $user->aktif)) @disabled($self)>
        Akun aktif
    </label>
@endif
