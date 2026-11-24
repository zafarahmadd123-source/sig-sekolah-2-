<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\ProfilSekolah;

class ProfilSekolahObserver
{
    public function created(ProfilSekolah $profil): void
    {
        ActivityLog::record(
            $profil->sekolah_id,
            $profil->sekolah->nama,
            'profile_created',
            $this->createdChanges($profil->getAttributes()),
        );
    }

    public function updated(ProfilSekolah $profil): void
    {
        $changes = [];
        $previous = $profil->getPrevious();

        foreach ($profil->getChanges() as $field => $value) {
            if (in_array($field, ['updated_at', 'sekolah_id'], true)) {
                continue;
            }

            $changes[$field] = [
                'old' => $previous[$field] ?? null,
                'new' => $value,
            ];
        }

        if ($changes !== []) {
            ActivityLog::record(
                $profil->sekolah_id,
                $profil->sekolah->nama,
                'profile_updated',
                $changes,
            );
        }
    }

    private function createdChanges(array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $field => $value) {
            if (in_array($field, ['id', 'sekolah_id', 'created_at', 'updated_at'], true)) {
                continue;
            }

            $changes[$field] = ['old' => null, 'new' => $value];
        }

        return $changes;
    }
}
