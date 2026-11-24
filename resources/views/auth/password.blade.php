@extends('layouts.app')

@section('title', 'Ubah Kata Sandi')

@section('content')
<h1 class="text-2xl font-bold mb-4">Ubah Kata Sandi</h1>

<form method="POST" action="{{ route('password.update') }}"
    class="max-w-xl bg-white rounded shadow p-6 space-y-4">
    @csrf
    @method('PUT')

    <div>
        <label for="current_password" class="block font-medium">Kata Sandi Saat Ini</label>
        <input type="password" id="current_password" name="current_password"
            autocomplete="current-password" required
            class="w-full border rounded px-3 py-2">
        @error('current_password')
            <p class="text-red-600 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block font-medium">Kata Sandi Baru</label>
        <input type="password" id="password" name="password"
            autocomplete="new-password" required
            class="w-full border rounded px-3 py-2">
        @error('password')
            <p class="text-red-600 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password_confirmation" class="block font-medium">
            Konfirmasi Kata Sandi Baru
        </label>
        <input type="password" id="password_confirmation"
            name="password_confirmation" autocomplete="new-password" required
            class="w-full border rounded px-3 py-2">
    </div>

    <button class="px-5 py-2 bg-blue-700 text-white rounded">
        Simpan Kata Sandi
    </button>
</form>
@endsection
