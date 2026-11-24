<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchoolCoordinateTest extends TestCase
{
    use RefreshDatabase;

    private function createSchool(): Sekolah
    {
        return Sekolah::create([
            'npsn' => '11111111',
            'nama' => 'Sekolah Uji',
            'jenjang' => 'SD',
            'status' => 'Negeri',
            'alamat' => 'Jl. Uji',
            'kecamatan' => 'Uji',
            'latitude' => -3.3,
            'longitude' => 114.59,
        ]);
    }

    private function schoolChanges(float $latitude, float $longitude): array
    {
        return [
            'npsn' => '11111111',
            'nama' => 'Sekolah Uji',
            'jenjang' => 'SD',
            'status' => 'Negeri',
            'alamat' => 'Jl. Uji',
            'kecamatan' => 'Uji',
            'latitude' => $latitude,
            'longitude' => $longitude,
        ];
    }

    public function test_school_operator_can_adjust_coordinates_within_one_kilometer(): void
    {
        $school = $this->createSchool();
        $operator = User::factory()->create([
            'role' => Role::Sekolah,
            'sekolah_id' => $school->id,
        ]);

        $this->actingAs($operator)
            ->put(route('sekolah.update', $school), $this->schoolChanges(-3.305, 114.59))
            ->assertRedirect();

        expect($school->fresh()->latitude)->toBe(-3.305);

        $log = ActivityLog::query()->where('action', 'updated')->firstOrFail();
        expect($log->user_id)->toBe($operator->id)
            ->and($log->changes)->toHaveKey('latitude')
            ->and($log->created_at)->not->toBeNull();
    }

    public function test_school_operator_cannot_move_coordinates_more_than_one_kilometer(): void
    {
        $school = $this->createSchool();
        $operator = User::factory()->create([
            'role' => Role::Sekolah,
            'sekolah_id' => $school->id,
        ]);

        $this->actingAs($operator)
            ->put(route('sekolah.update', $school), $this->schoolChanges(-3.32, 114.59))
            ->assertSessionHasErrors('latitude');

        expect($school->fresh()->latitude)->toBe(-3.3);
    }

    public function test_school_operator_cannot_exceed_the_original_radius_by_repeated_small_changes(): void
    {
        $school = $this->createSchool();
        $operator = User::factory()->create([
            'role' => Role::Sekolah,
            'sekolah_id' => $school->id,
        ]);

        $this->actingAs($operator)
            ->put(route('sekolah.update', $school), $this->schoolChanges(-3.305, 114.59))
            ->assertRedirect();

        $this->put(route('sekolah.update', $school), $this->schoolChanges(-3.31, 114.59))
            ->assertSessionHasErrors('latitude');

        expect($school->fresh()->latitude)->toBe(-3.305);
    }

    public function test_dinas_can_correct_school_coordinates_and_update_the_baseline(): void
    {
        $school = $this->createSchool();
        $dinas = User::factory()->create(['role' => Role::Dinas]);

        $this->actingAs($dinas)
            ->put(route('dinas.sekolah.update', $school), $this->schoolChanges(-3.32, 114.59))
            ->assertRedirect();

        expect($school->fresh()->baseline_latitude)->toBe(-3.32);
    }
}
