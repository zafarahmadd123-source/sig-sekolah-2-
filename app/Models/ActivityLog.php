<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'sekolah_id',
        'sekolah_nama',
        'action',
        'changes',
    ];

    protected function casts(): array
    {
        return [
            'changes' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sekolah(): BelongsTo
    {
        return $this->belongsTo(Sekolah::class);
    }

    public static function record(
        ?int $sekolahId,
        string $sekolahNama,
        string $action,
        array $changes,
    ): void {
        $user = Auth::user();

        self::create([
            'user_id' => $user?->getAuthIdentifier(),
            'user_name' => $user?->name,
            'sekolah_id' => $sekolahId,
            'sekolah_nama' => $sekolahNama,
            'action' => $action,
            'changes' => $changes,
        ]);
    }
}
