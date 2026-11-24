<?php

namespace App\Observers;

use App\Models\ActivityLog;
use App\Models\Sekolah;

class SekolahObserver
{
    public function creating(Sekolah $sekolah): void
    {
        $sekolah->baseline_latitude = $sekolah->latitude;
        $sekolah->baseline_longitude = $sekolah->longitude;
    }

    public function created(Sekolah $sekolah): void
    {
        ActivityLog::record(
            $sekolah->id,
            $sekolah->nama,
            'created',
            $this->createdChanges($sekolah->getAttributes()),
        );
    }

    public function updated(Sekolah $sekolah): void
    {
        $changes = $this->updatedChanges($sekolah);

        if ($changes !== []) {
            ActivityLog::record($sekolah->id, $sekolah->nama, 'updated', $changes);
        }
    }

    public function deleted(Sekolah $sekolah): void
    {
        ActivityLog::record(
            null,
            $sekolah->nama,
            'deleted',
            $this->deletedChanges($sekolah->getAttributes()),
        );
    }

    private function createdChanges(array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $field => $value) {
            if (in_array($field, [
                'id',
                'created_at',
                'updated_at',
                'baseline_latitude',
                'baseline_longitude',
            ], true)) {
                continue;
            }

            $changes[$field] = ['old' => null, 'new' => $value];
        }

        return $changes;
    }

    private function deletedChanges(array $attributes): array
    {
        $changes = [];

        foreach ($attributes as $field => $value) {
            if (in_array($field, [
                'id',
                'created_at',
                'updated_at',
                'baseline_latitude',
                'baseline_longitude',
            ], true)) {
                continue;
            }

            $changes[$field] = ['old' => $value, 'new' => null];
        }

        return $changes;
    }

    private function updatedChanges(Sekolah $sekolah): array
    {
        $changes = [];
        $previous = $sekolah->getPrevious();

        foreach ($sekolah->getChanges() as $field => $value) {
            if (in_array($field, [
                'updated_at',
                'baseline_latitude',
                'baseline_longitude',
            ], true)) {
                continue;
            }

            $changes[$field] = [
                'old' => $previous[$field] ?? null,
                'new' => $value,
            ];
        }

        return $changes;
    }
}
