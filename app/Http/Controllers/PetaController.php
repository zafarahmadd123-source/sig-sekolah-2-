<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PetaController extends Controller
{
    private const SEARCH_RESULTS_PER_PAGE = 50;

    public function index()
    {
        return view('peta.index');
    }

    public function cari()
    {
        return view('peta.cari');
    }

    public function klaster()
    {
        return view('peta.klaster');
    }

    public function panas()
    {
        return view('peta.panas');
    }

    public function rute()
    {
        return view('peta.rute');
    }

    public function alamat()
    {
        return view('peta.alamat');
    }

    public function geojson(Request $request)
    {
        $filters = $request->validate([
            'jenjang' => ['nullable', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'status' => ['nullable', Rule::in(['Negeri', 'Swasta'])],
            'south' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'west' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
            'north' => ['sometimes', 'required', 'numeric', 'between:-90,90'],
            'east' => ['sometimes', 'required', 'numeric', 'between:-180,180'],
        ]);
        $boundNames = ['south', 'west', 'north', 'east'];

        if (count(array_intersect($boundNames, array_keys($filters))) !== 4) {
            throw ValidationException::withMessages([
                'bounds' => 'Batas peta wajib berisi south, west, north, dan east.',
            ]);
        }

        if ($filters['south'] >= $filters['north'] || $filters['west'] >= $filters['east']) {
            throw ValidationException::withMessages([
                'bounds' => 'Batas peta tidak valid.',
            ]);
        }

        $query = $this->sekolahDenganProfil()
            ->when($filters['jenjang'] ?? null, fn ($query, $jenjang) => $query->where('jenjang', $jenjang))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));

        $query->whereBetween('latitude', [$filters['south'], $filters['north']])
            ->whereBetween('longitude', [$filters['west'], $filters['east']]);

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $this->fiturGeoJSON($query->get()),
        ]);
    }

    public function cariGeojson(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:100'],
            'jenjang' => ['nullable', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'page' => ['sometimes', 'integer', 'min:1'],
        ]);
        $kataKunci = trim($filters['q']);

        $pencarian = '%'.$kataKunci.'%';
        $hasil = $this->sekolahDenganProfil()
            ->where(function (Builder $query) use ($pencarian): void {
                $query->where('nama', 'like', $pencarian)
                    ->orWhere('npsn', 'like', $pencarian)
                    ->orWhere('kecamatan', 'like', $pencarian);
            })
            ->when($filters['jenjang'] ?? null, fn ($query, $jenjang) => $query->where('jenjang', $jenjang))
            ->orderBy('nama')
            ->paginate(
                self::SEARCH_RESULTS_PER_PAGE,
                ['*'],
                'page',
                $filters['page'] ?? 1,
            );

        return response()->json([
            'type' => 'FeatureCollection',
            'features' => $this->fiturGeoJSON($hasil->getCollection()),
            'pagination' => [
                'current_page' => $hasil->currentPage(),
                'last_page' => $hasil->lastPage(),
                'per_page' => $hasil->perPage(),
                'total' => $hasil->total(),
            ],
        ]);
    }

    public function wilayah()
    {
        return view('peta.wilayah');
    }

    public function geojsonWilayah(Request $request)
    {
        $filters = $request->validate([
            'jenjang' => ['nullable', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'status' => ['nullable', Rule::in(['Negeri', 'Swasta'])],
        ]);

        $jumlahSekolahPerKecamatan = Sekolah::query()
            ->select('kecamatan')
            ->selectRaw('COUNT(*) as jumlah_sekolah')
            ->when($filters['jenjang'] ?? null, fn ($query, $jenjang) => $query->where('jenjang', $jenjang))
            ->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->groupBy('kecamatan')
            ->get()
            ->mapWithKeys(fn ($row) => [$row->kecamatan => (int) $row->jumlah_sekolah]);

        return response()->json([
            'type' => 'FeatureCollection',
            'sekolah_per_kecamatan' => $jumlahSekolahPerKecamatan,
        ]);
    }

    private function sekolahDenganProfil(): Builder
    {
        return Sekolah::query()
            ->select([
                'id', 'npsn', 'nama', 'jenjang', 'status', 'alamat',
                'kecamatan', 'telepon', 'latitude', 'longitude',
            ])
            ->with('profil:id,sekolah_id,kepala_sekolah,akreditasi,jumlah_siswa,jumlah_guru,logo_path');
    }

    private function fiturGeoJSON(EloquentCollection $sekolah): Collection
    {
        return $sekolah->map(fn (Sekolah $item) => [
            'type' => 'Feature',
            'geometry' => [
                'type' => 'Point',
                'coordinates' => [$item->longitude, $item->latitude],
            ],
            'properties' => [
                'npsn' => $item->npsn,
                'nama' => $item->nama,
                'jenjang' => $item->jenjang,
                'status' => $item->status,
                'alamat' => $item->alamat,
                'kecamatan' => $item->kecamatan,
                'telepon' => $item->telepon,
                'kepala_sekolah' => $item->profil?->kepala_sekolah,
                'akreditasi' => $item->profil?->akreditasi,
                'jumlah_siswa' => $item->profil?->jumlah_siswa,
                'jumlah_guru' => $item->profil?->jumlah_guru,
                'logo_url' => $item->profil?->logoUrl(),
                'profil_url' => route('sekolah.public.show', $item),
            ],
        ]);
    }
}
