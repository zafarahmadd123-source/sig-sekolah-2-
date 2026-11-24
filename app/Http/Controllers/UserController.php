<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('sekolah')
            ->when($request->role, fn ($q, $r) => $q->where('role', $r))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create', [
            'user' => new User,
            'sekolahs' => $this->daftarSekolah(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules($request));

        if ($data['role'] !== Role::Sekolah->value) {
            $data['sekolah_id'] = null;
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        User::create($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.users.edit', [
            'user' => $user,
            'sekolahs' => $this->daftarSekolah(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($request, $user->id));

        if ($user->is($request->user()) && $data['role'] !== Role::Admin->value) {
            return back()->withInput()->withErrors([
                'role' => 'Anda tidak dapat mengubah role akun sendiri.',
            ]);
        }

        if ($user->is($request->user()) && ! $data['is_active']) {
            return back()->withInput()->withErrors([
                'is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            ]);
        }

        if ($data['role'] !== Role::Sekolah->value) {
            $data['sekolah_id'] = null;
        }

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->is($request->user())) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    private function daftarSekolah()
    {
        return Sekolah::orderBy('nama')->get(['id', 'npsn', 'nama']);
    }

    private function rules(Request $request, ?int $id = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($id)],
            'role' => ['required', Rule::enum(Role::class)],
            'is_active' => ['required', 'boolean'],
            'sekolah_id' => [
                Rule::requiredIf($request->role === Role::Sekolah->value),
                'nullable', 'exists:sekolahs,id',
            ],
            'password' => [$id ? 'nullable' : 'required',
                'string', 'min:8', 'confirmed'],
        ];
    }
}
