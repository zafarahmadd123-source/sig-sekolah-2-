<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = $request->user();

        $jumlahPerRole = null;
        $jumlahSekolah = null;
        $sekolah = null;
        $kelengkapan = 0;

        if ($user->isAdmin()) {
            $jumlahPerRole = [
                Role::Admin->label() => User::where('role', Role::Admin->value)->count(),
                Role::Dinas->label() => User::where('role', Role::Dinas->value)->count(),
                Role::Sekolah->label() => User::where('role', Role::Sekolah->value)->count(),
            ];
        } elseif ($user->isDinas()) {
            $jumlahSekolah = Sekolah::count();
        } elseif ($user->sekolah) {
            $sekolah = $user->sekolah->load('profil');
            $kolom = [
                'kepala_sekolah', 'email', 'website', 'tahun_berdiri',
                'akreditasi', 'jumlah_siswa', 'jumlah_guru', 'deskripsi',
            ];
            $profil = $sekolah->profil;
            $terisi = $profil
                ? collect($kolom)->filter(fn ($k) => filled($profil->$k))->count()
                : 0;
            $kelengkapan = (int) round($terisi / count($kolom) * 100);
        }

        return view('dashboard', compact(
            'user', 'jumlahPerRole', 'jumlahSekolah', 'sekolah', 'kelengkapan'
        ));
    }
}
