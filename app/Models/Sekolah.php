<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Sekolah extends Model
{
    public const MAX_COORDINATE_DRIFT_METERS = 1000;

    protected $fillable = [
        'npsn', 'nama', 'jenjang', 'status', 'alamat',
        'kecamatan', 'telepon', 'latitude', 'longitude',
    ];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'baseline_latitude' => 'float',
            'baseline_longitude' => 'float',
        ];
    }

    public function isWithinOriginalCoordinateRadius(float $latitude, float $longitude): bool
    {
        $originLatitude = $this->baseline_latitude ?? $this->latitude;
        $originLongitude = $this->baseline_longitude ?? $this->longitude;
        $earthRadiusMeters = 6_371_000;
        $latitudeDelta = deg2rad($latitude - $originLatitude);
        $longitudeDelta = deg2rad($longitude - $originLongitude);
        $haversine = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($originLatitude))
            * cos(deg2rad($latitude))
            * sin($longitudeDelta / 2) ** 2;
        $distanceMeters = 2 * $earthRadiusMeters * asin(min(1, sqrt($haversine)));

        return $distanceMeters <= self::MAX_COORDINATE_DRIFT_METERS;
    }

    public function profil(): HasOne
    {
        return $this->hasOne(ProfilSekolah::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
