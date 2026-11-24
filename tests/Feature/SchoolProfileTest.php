<?php

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\ActivityLog;
use App\Models\ProfilSekolah;
use App\Models\Sekolah;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SchoolProfileTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

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

    public function test_school_can_upload_logo_and_it_is_returned_for_map_popup(): void
    {
        Storage::fake('public');
        $school = $this->createSchool();
        $operator = User::factory()->create([
            'role' => Role::Sekolah,
            'sekolah_id' => $school->id,
        ]);

        $this->actingAs($operator)->put(route('sekolah.profil.update', $school), [
            'kepala_sekolah' => 'Ibu Uji',
            'logo' => UploadedFile::fake()->image('logo.png'),
        ])->assertRedirect();

        $profile = ProfilSekolah::where('sekolah_id', $school->id)->firstOrFail();
        Storage::disk('public')->assertExists($profile->logo_path);

        $this->get(route('peta.data', [
            'south' => -3.4,
            'west' => 114.5,
            'north' => -3.2,
            'east' => 114.7,
        ]))
            ->assertOk()
            ->assertJsonPath('features.0.properties.logo_url', Storage::disk('public')->url($profile->logo_path))
            ->assertJsonPath('features.0.properties.profil_url', route('sekolah.public.show', $school));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $operator->id,
            'sekolah_id' => $school->id,
            'action' => 'profile_created',
        ]);
    }

    public function test_school_can_upload_a_logo_larger_than_two_megabytes(): void
    {
        Storage::fake('public');
        $school = $this->createSchool();
        $operator = User::factory()->create([
            'role' => Role::Sekolah,
            'sekolah_id' => $school->id,
        ]);

        $this->actingAs($operator)->put(route('sekolah.profil.update', $school), [
            'logo' => UploadedFile::fake()->image('logo.png')->size(3072),
        ])->assertRedirect();

        $profile = ProfilSekolah::where('sekolah_id', $school->id)->firstOrFail();
        Storage::disk('public')->assertExists($profile->logo_path);
    }

    public function test_logo_upload_rejects_non_image_files(): void
    {
        Storage::fake('public');
        $school = $this->createSchool();
        $operator = User::factory()->create([
            'role' => Role::Sekolah,
            'sekolah_id' => $school->id,
        ]);

        $this->actingAs($operator)->put(route('sekolah.profil.update', $school), [
            'logo' => UploadedFile::fake()->create('logo.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors('logo');

        $this->assertDatabaseMissing('profil_sekolahs', ['sekolah_id' => $school->id]);
    }

    public function test_public_school_profile_displays_school_details_safely(): void
    {
        $school = $this->createSchool();
        $school->profil()->create([
            'deskripsi' => '<script>alert(1)</script>',
            'kepala_sekolah' => 'Kepala Uji',
        ]);

        $this->get(route('sekolah.public.show', $school))
            ->assertOk()
            ->assertSee('Kepala Uji')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_profile_changes_are_logged_with_the_user_and_changed_values(): void
    {
        $school = $this->createSchool();
        $operator = User::factory()->create([
            'role' => Role::Sekolah,
            'sekolah_id' => $school->id,
        ]);
        $school->profil()->create(['kepala_sekolah' => 'Nama Lama']);

        $this->actingAs($operator)->put(route('sekolah.profil.update', $school), [
            'kepala_sekolah' => 'Nama Baru',
        ])->assertRedirect();

        $log = ActivityLog::query()->where('action', 'profile_updated')->firstOrFail();
        expect($log->user_id)->toBe($operator->id)
            ->and($log->changes['kepala_sekolah'])->toBe([
                'old' => 'Nama Lama',
                'new' => 'Nama Baru',
            ])
            ->and($log->created_at)->not->toBeNull();
    }
}
