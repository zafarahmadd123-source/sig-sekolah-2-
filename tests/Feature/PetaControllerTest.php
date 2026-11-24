<?php

namespace Tests\Feature;

use App\Models\Sekolah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PetaControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_map_menus_are_public_and_render_the_requested_controls(): void
    {
        $this->get(route('peta.index'))
            ->assertOk()
            ->assertSee('filter-status')
            ->assertSee('Unduh GeoJSON')
            ->assertSee('L.control.layers');

        $this->get(route('peta.klaster'))
            ->assertOk()
            ->assertSee('filter-status');

        $this->get(route('peta.alamat'))
            ->assertOk()
            ->assertSee('Lokasi saya')
            ->assertSee('L.circle');
    }

    public function test_map_data_applies_status_and_visible_map_bounds(): void
    {
        $this->createSchool('11111111', 'Negeri Dalam Peta', 'Negeri', -3.30, 114.59);
        $this->createSchool('22222222', 'Swasta Dalam Peta', 'Swasta', -3.30, 114.59);
        $this->createSchool('33333333', 'Negeri Di Luar Peta', 'Negeri', -3.50, 114.59);

        $this->getJson(route('peta.data', [
            'status' => 'Negeri',
            'south' => -3.35,
            'west' => 114.55,
            'north' => -3.25,
            'east' => 114.65,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.npsn', '11111111');
    }

    public function test_map_data_rejects_incomplete_or_inverted_bounds(): void
    {
        $this->getJson(route('peta.data'))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bounds');

        $this->getJson(route('peta.data', ['south' => -3.35]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bounds');

        $this->getJson(route('peta.data', [
            'south' => -3.25,
            'west' => 114.65,
            'north' => -3.35,
            'east' => 114.55,
        ]))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('bounds');

        $this->getJson(route('peta.data', ['status' => 'Tidak diketahui']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('status');
    }

    public function test_school_search_matches_name_npsn_and_district(): void
    {
        $this->createSchool('11111111', 'SD Negeri Melati', 'Negeri', -3.30, 114.59);
        $this->createSchool(
            '22222222',
            'SMP Harapan',
            'Swasta',
            -3.31,
            114.61,
            'Banjarmasin Timur',
            'SMP',
        );

        $this->getJson(route('peta.data.cari', ['q' => 'Melati']))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.npsn', '11111111');

        $this->getJson(route('peta.data.cari', ['q' => '11111111']))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.nama', 'SD Negeri Melati');

        $this->getJson(route('peta.data.cari', ['q' => 'Banjarmasin Timur']))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.npsn', '22222222');

        $this->getJson(route('peta.data.cari', [
            'q' => 'Banjarmasin',
            'jenjang' => 'SMP',
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.properties.npsn', '22222222');
    }

    public function test_school_search_paginates_matches_and_rejects_short_search_terms(): void
    {
        for ($index = 1; $index <= 51; $index++) {
            $this->createSchool(
                sprintf('8%07d', $index),
                "Sekolah Demo {$index}",
                'Negeri',
                -3.30,
                114.59,
                'Banjarmasin Barat',
            );
        }

        $this->getJson(route('peta.data.cari', ['q' => 'Banjarmasin Barat']))
            ->assertOk()
            ->assertJsonCount(50, 'features')
            ->assertJsonPath('pagination.total', 51)
            ->assertJsonPath('pagination.current_page', 1);

        $this->getJson(route('peta.data.cari', [
            'q' => 'Banjarmasin Barat',
            'page' => 2,
        ]))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('pagination.current_page', 2);

        $this->getJson(route('peta.data.cari', ['q' => 'x']))
            ->assertInvalid(['q' => 'The q field must be at least 2 characters.']);
    }

    public function test_district_totals_use_official_boundary_names_and_apply_status_filter(): void
    {
        $this->createSchool('11111111', 'Negeri Utara', 'Negeri', -3.30, 114.59, 'Banjarmasin Utara');
        $this->createSchool('22222222', 'Swasta Utara', 'Swasta', -3.30, 114.59, 'Banjarmasin Utara');
        $this->createSchool('33333333', 'Swasta Timur', 'Swasta', -3.31, 114.61, 'Banjarmasin Timur');

        $this->getJson(route('peta.data.wilayah', ['status' => 'Swasta']))
            ->assertOk()
            ->assertJsonPath('sekolah_per_kecamatan.Banjarmasin Utara', 1)
            ->assertJsonPath('sekolah_per_kecamatan.Banjarmasin Timur', 1)
            ->assertJsonMissingPath('sekolah_per_kecamatan.Banjarmasin Selatan');
    }

    public function test_boundary_file_contains_the_five_banjarmasin_districts(): void
    {
        $geojson = json_decode(
            file_get_contents(public_path('geojson/batas-kecamatan-banjarmasin.geojson')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );
        $kecamatan = array_column(
            array_column($geojson['features'], 'properties'),
            'WADMKC',
        );
        sort($kecamatan);

        expect($geojson['type'])->toBe('FeatureCollection')
            ->and($kecamatan)->toBe([
                'Banjarmasin Barat',
                'Banjarmasin Selatan',
                'Banjarmasin Tengah',
                'Banjarmasin Timur',
                'Banjarmasin Utara',
            ]);
    }

    private function createSchool(
        string $npsn,
        string $nama,
        string $status,
        float $latitude,
        float $longitude,
        string $kecamatan = 'Banjarmasin Utara',
        string $jenjang = 'SD',
    ): Sekolah {
        return Sekolah::create([
            'npsn' => $npsn,
            'nama' => $nama,
            'jenjang' => $jenjang,
            'status' => $status,
            'alamat' => 'Jl. Uji',
            'kecamatan' => $kecamatan,
            'latitude' => $latitude,
            'longitude' => $longitude,
        ]);
    }
}
