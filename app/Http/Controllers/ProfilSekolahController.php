<?php

namespace App\Http\Controllers;

use App\Models\ProfilSekolah;
use App\Models\Sekolah;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class ProfilSekolahController extends Controller
{
    public function edit(Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $profil = $sekolah->profil ?? new ProfilSekolah;

        return view('admin.sekolah.profil', compact('sekolah', 'profil'));
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $logo = $request->file('logo');

        if ($logo instanceof UploadedFile && ! $logo->isValid()) {
            $message = match ($logo->getError()) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                    'Ukuran logo melewati batas unggah server. Coba file yang lebih kecil dari 5 MB.',
                UPLOAD_ERR_PARTIAL =>
                    'Unggahan logo terputus. Silakan coba lagi.',
                UPLOAD_ERR_NO_TMP_DIR =>
                    'Direktori sementara unggahan PHP tidak tersedia. Administrator perlu memeriksa upload_tmp_dir.',
                UPLOAD_ERR_CANT_WRITE =>
                    'PHP tidak dapat menulis file unggahan sementara. Periksa izin direktori sementara dan ruang disk.',
                UPLOAD_ERR_EXTENSION =>
                    'Unggahan logo dihentikan oleh konfigurasi server.',
                default =>
                    'Logo gagal diterima oleh server. Silakan coba lagi.',
            };

            throw ValidationException::withMessages(['logo' => $message]);
        }

        $data = $request->validate([
            'kepala_sekolah' => ['nullable', 'string', 'max:150'],
            'email' => ['nullable', 'email', 'max:150'],
            'website' => ['nullable', 'url:http,https', 'max:255'],
            'tahun_berdiri' => ['nullable', 'integer',
                'between:1800,'.date('Y')],
            'akreditasi' => ['nullable', Rule::in(
                ['A', 'B', 'C', 'Belum Terakreditasi'])],
            'jumlah_siswa' => ['nullable', 'integer', 'min:0'],
            'jumlah_guru' => ['nullable', 'integer', 'min:0'],
            'deskripsi' => ['nullable', 'string', 'max:2000'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        unset($data['logo']);

        $profil = $sekolah->profil;
        $oldLogoPath = $profil?->logo_path;
        $newLogoPath = null;

        if ($request->hasFile('logo')) {
            $newLogoPath = $request->file('logo')->storePublicly('logos', 'public');

            if ($newLogoPath === false) {
                throw new RuntimeException('Logo sekolah gagal disimpan.');
            }

            $data['logo_path'] = $newLogoPath;
        }

        try {
            DB::transaction(fn () => $sekolah->profil()->updateOrCreate([], $data));
        } catch (Throwable $exception) {
            if ($newLogoPath !== null) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if ($newLogoPath !== null && $oldLogoPath !== null
            && ! Storage::disk('public')->delete($oldLogoPath)) {
            Log::warning('Logo profil sekolah lama gagal dihapus.', [
                'sekolah_id' => $sekolah->id,
                'path' => $oldLogoPath,
            ]);
        }

        return back()->with('success', 'Profil sekolah berhasil disimpan.');
    }
}
