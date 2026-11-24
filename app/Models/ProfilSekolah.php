<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProfilSekolah extends Model
{
    protected $fillable = [
        'sekolah_id',
        'kepala_sekolah',
        'email',
        'website',
        'tahun_berdiri',
        'akreditasi',
        'jumlah_siswa',
        'jumlah_guru',
        'deskripsi',
        'logo_path',
    ];

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public function logoUrl(): ?string
    {
        return $this->logo_path === null
            ? null
            : Storage::disk('public')->url($this->logo_path);
    }
}
