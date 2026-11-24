<?php

use App\Enums\Role;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PetaController;
use App\Http\Controllers\ProfilSekolahController;
use App\Http\Controllers\SekolahController;
use App\Http\Controllers\SekolahPublikController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Rute Publik
|--------------------------------------------------------------------------
*/
Route::get('/', [PetaController::class, 'index'])->name('peta.index');
Route::get('/data/sekolah', [PetaController::class, 'geojson'])->name('peta.data');
Route::get('/data/pencarian-sekolah', [PetaController::class, 'cariGeojson'])->name('peta.data.cari');
Route::controller(PetaController::class)->group(function () {
    Route::get('/pencarian', 'cari')->name('peta.cari');
    Route::get('/klaster', 'klaster')->name('peta.klaster');
    Route::get('/peta-panas', 'panas')->name('peta.panas');
    Route::get('/wilayah', 'wilayah')->name('peta.wilayah');
    Route::get('/data/wilayah', 'geojsonWilayah')->name('peta.data.wilayah');
    Route::get('/rute', 'rute')->name('peta.rute');
    Route::get('/cek-alamat', 'alamat')->name('peta.alamat');
});
Route::get('/sekolah/{sekolah}', [SekolahPublikController::class, 'show'])
    ->name('sekolah.public.show');

/*
|--------------------------------------------------------------------------
| Rute Tamu (Guest)
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| Rute Terproteksi (Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/kata-sandi', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/kata-sandi', [PasswordController::class, 'update'])->name('password.update');

    // 1. Group Utama Dinas (Prefix URL /dinas/ & Prefix Nama dinas.)
    Route::prefix('dinas')->name('dinas.')->group(function () {
        Route::middleware('role:'.Role::Dinas->value)->group(function () {
            Route::resource('sekolah', SekolahController::class)
                ->only(['index', 'create', 'store', 'destroy']);
        });

        Route::middleware('role:'.Role::Dinas->value.','.Role::Sekolah->value)->group(function () {
            Route::get('sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])->name('sekolah.edit');
            Route::put('sekolah/{sekolah}', [SekolahController::class, 'update'])->name('sekolah.update');

            Route::get('sekolah/{sekolah}/profil', [ProfilSekolahController::class, 'edit'])->name('sekolah.profil.edit');
            Route::put('sekolah/{sekolah}/profil', [ProfilSekolahController::class, 'update'])->name('sekolah.profil.update');
        });
    });

    // 2. Group Rute Tanpa Prefix /dinas/ (Untuk Memenuhi Skenario RbacTest & Compatibility)
    Route::middleware('role:'.Role::Dinas->value.','.Role::Sekolah->value)->group(function () {
        Route::get('sekolah/{sekolah}/edit', [SekolahController::class, 'edit'])->name('sekolah.edit');
        Route::put('sekolah/{sekolah}', [SekolahController::class, 'update'])->name('sekolah.update');

        Route::get('sekolah/{sekolah}/profil', [ProfilSekolahController::class, 'edit'])->name('sekolah.profil.edit');
        Route::put('sekolah/{sekolah}/profil', [ProfilSekolahController::class, 'update'])->name('sekolah.profil.update');
    });

    // 3. Admin: Kelola Pengguna
    Route::middleware('role:'.Role::Admin->value)->prefix('admin')->name('admin.')->group(function () {
        Route::resource('users', UserController::class)->except('show');
    });
});
