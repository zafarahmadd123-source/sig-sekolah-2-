@php $cls = 'w-full border rounded px-3 py-2'; @endphp

<div class="bg-white rounded shadow p-6 max-w-2xl space-y-4">
    <div>
        <label for="name" class="block font-medium">Nama</label>
        <input type="text" id="name" name="name"
            value="{{ old('name', $user->name) }}" class="{{ $cls }}">
        @error('name')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="email" class="block font-medium">Email</label>
        <input type="email" id="email" name="email"
            value="{{ old('email', $user->email) }}" class="{{ $cls }}">
        @error('email')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="role" class="block font-medium">Role</label>
        <select id="role" name="role" class="{{ $cls }}">
            @foreach (\App\Enums\Role::cases() as $role)
                <option value="{{ $role->value }}"
                    @selected(old('role', $user->role?->value ?? \App\Enums\Role::Sekolah->value) === $role->value)>
                    {{ $role->label() }}
                </option>
            @endforeach
        </select>
        @error('role')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <input type="hidden" name="is_active" value="0">
        <label class="flex items-center gap-2 font-medium">
            <input type="checkbox" name="is_active" value="1"
                @checked((bool) old('is_active', $user->exists ? $user->is_active : true))>
            Akun aktif
        </label>
        @error('is_active')
            <p class="text-red-600 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div id="blok-sekolah">
        <label for="sekolah_id" class="block font-medium">Sekolah</label>
        <select id="sekolah_id" name="sekolah_id" class="{{ $cls }}">
            <option value="">-- Pilih sekolah --</option>
            @foreach ($sekolahs as $s)
                <option value="{{ $s->id }}"
                    @selected((int) old('sekolah_id', $user->sekolah_id) === $s->id)>
                    {{ $s->npsn }} - {{ $s->nama }}
                </option>
            @endforeach
        </select>
        @error('sekolah_id')
            <p class="text-red-600 text-sm">{{ $message }}</p>
        @enderror
    </div>

    <div>
        <label for="password" class="block font-medium">Kata Sandi</label>
        <input type="password" id="password" name="password"
            class="{{ $cls }}" autocomplete="new-password">
        @if ($user->exists)
            <p class="text-gray-500 text-sm">Kosongkan jika tidak diganti.</p>
        @endif
        @error('password')<p class="text-red-600 text-sm">{{ $message }}</p>@enderror
    </div>

    <div>
        <label for="password_confirmation" class="block font-medium">
            Ulangi Kata Sandi
        </label>
        <input type="password" id="password_confirmation"
            name="password_confirmation" class="{{ $cls }}"
            autocomplete="new-password">
    </div>

    <div class="flex gap-3 pt-2">
        <button class="px-5 py-2 bg-blue-700 text-white rounded">Simpan</button>
        <a href="{{ route('admin.users.index') }}"
            class="px-5 py-2 bg-gray-300 rounded">Batal</a>
    </div>
</div>

@push('scripts')
<script>
    const role = document.getElementById('role');
    const blok = document.getElementById('blok-sekolah');
    const roleSekolah = @json(\App\Enums\Role::Sekolah->value);

    function aturBlokSekolah() {
        blok.classList.toggle('hidden', role.value !== roleSekolah);
    }

    role.addEventListener('change', aturBlokSekolah);
    aturBlokSekolah();
</script>
@endpush