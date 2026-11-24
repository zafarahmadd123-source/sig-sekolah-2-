<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class SekolahController extends Controller
{
    public function index(Request $request)
    {
        $sekolahs = Sekolah::query()
    ->when($request->q, function ($query, $q) {
        $query->where(function ($w) use ($q) {
            $w->whereLike('nama', "%{$q}%")
              ->orWhereLike('npsn', "%{$q}%")
              ->orWhereLike('kecamatan', "%{$q}%");
        });
    })
    ->orderBy('nama')
    ->paginate(10)
    ->withQueryString();

        return view('admin.sekolah.index', compact('sekolahs'));
    }

    public function create()
    {
        return view('admin.sekolah.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        DB::transaction(fn () => Sekolah::create($data));

        return redirect()->route('dinas.sekolah.index')
            ->with('success', 'Data sekolah berhasil ditambahkan.');
    }

    public function edit(Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        return view('admin.sekolah.edit', compact('sekolah'));
    }

    public function update(Request $request, Sekolah $sekolah)
    {
        Gate::authorize('update', $sekolah);

        $data = $request->validate($this->rules($sekolah->id));

        if ($request->user()->isSekolah()
            && ! $sekolah->isWithinOriginalCoordinateRadius(
                (float) $data['latitude'],
                (float) $data['longitude'],
            )) {
            $maximumDistanceKm = number_format(
                Sekolah::MAX_COORDINATE_DRIFT_METERS / 1000,
                0,
                ',',
                '.',
            );

            throw ValidationException::withMessages([
                'latitude' => "Perubahan koordinat dibatasi maksimal {$maximumDistanceKm} km dari titik awal.",
                'longitude' => "Perubahan koordinat dibatasi maksimal {$maximumDistanceKm} km dari titik awal.",
            ]);
        }

        // NPSN adalah identitas resmi: tidak boleh diubah oleh sekolah
        if ($request->user()->isSekolah()) {
            unset($data['npsn']);
        }

        $sekolah->fill($data);

        if ($request->user()->isDinas() && $sekolah->isDirty(['latitude', 'longitude'])) {
            $sekolah->baseline_latitude = $data['latitude'];
            $sekolah->baseline_longitude = $data['longitude'];
        }

        DB::transaction(fn () => $sekolah->save());

        return $this->kembali($request)
            ->with('success', 'Data sekolah berhasil diperbarui.');
    }

    public function destroy(Sekolah $sekolah)
    {
        DB::transaction(fn () => $sekolah->delete());

        return redirect()->route('dinas.sekolah.index')
            ->with('success', 'Data sekolah berhasil dihapus.');
    }

    private function kembali(Request $request)
    {
        return $request->user()->isDinas()
            ? redirect()->route('dinas.sekolah.index')
            : redirect()->route('dashboard');
    }

    private function rules(?int $id = null): array
    {
        return [
            'npsn' => ['required', 'digits:8',
                Rule::unique('sekolahs', 'npsn')->ignore($id)],
            'nama' => ['required', 'string', 'max:255'],
            'jenjang' => ['required', Rule::in(['SD', 'SMP', 'SMA', 'SMK'])],
            'status' => ['required', Rule::in(['Negeri', 'Swasta'])],
            'alamat' => ['required', 'string'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'telepon' => ['nullable', 'string', 'max:20'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
        ];
    }
}
