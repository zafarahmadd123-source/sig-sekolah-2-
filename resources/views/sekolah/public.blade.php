@extends('layouts.app')

@section('title', 'Profil '.$sekolah->nama)

@section('content')
@php
    $profil = $sekolah->profil;
    $detail = [
        'NPSN' => $sekolah->npsn,
        'Jenjang' => $sekolah->jenjang,
        'Status' => $sekolah->status,
        'Alamat' => $sekolah->alamat,
        'Kecamatan' => $sekolah->kecamatan,
        'Telepon' => $sekolah->telepon,
        'Kepala Sekolah' => $profil?->kepala_sekolah,
        'Email' => $profil?->email,
        'Website' => $profil?->website,
        'Tahun Berdiri' => $profil?->tahun_berdiri,
        'Akreditasi' => $profil?->akreditasi,
        'Jumlah Siswa' => $profil?->jumlah_siswa,
        'Jumlah Guru' => $profil?->jumlah_guru,
    ];
@endphp

<article class="max-w-4xl mx-auto bg-white rounded shadow p-6 space-y-6">
    <header class="flex flex-col sm:flex-row sm:items-center gap-5">
        @if ($profil?->logoUrl())
            <img src="{{ $profil->logoUrl() }}" alt="Logo {{ $sekolah->nama }}"
                class="h-28 w-28 rounded object-contain">
        @endif
        <div>
            <h1 class="text-3xl font-bold">{{ $sekolah->nama }}</h1>
            <p class="text-gray-600">Profil publik sekolah</p>
        </div>
    </header>

    <dl class="grid sm:grid-cols-2 gap-4">
        @foreach ($detail as $label => $value)
            <div class="border-b pb-2">
                <dt class="text-sm text-gray-500">{{ $label }}</dt>
                <dd class="font-medium">
                    @if ($label === 'Website' && $value
                        && in_array(parse_url($value, PHP_URL_SCHEME), ['http', 'https'], true))
                        <a href="{{ $value }}" class="text-blue-700 underline"
                            rel="noopener noreferrer">{{ $value }}</a>
                    @else
                        {{ $value ?: '-' }}
                    @endif
                </dd>
            </div>
        @endforeach
    </dl>

    @if (filled($profil?->deskripsi))
        <section>
            <h2 class="text-xl font-semibold mb-2">Tentang Sekolah</h2>
            <p class="whitespace-pre-line">{{ $profil->deskripsi }}</p>
        </section>
    @endif
</article>
@endsection
