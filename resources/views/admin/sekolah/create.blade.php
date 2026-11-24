@extends('layouts.app')
@section('title', 'Tambah Sekolah')
@section('content')
<h1 class="mb-4 text-2xl font-bold">Tambah Sekolah</h1>
<form method="POST" action="{{ route('dinas.sekolah.store') }}"
    class="rounded bg-white p-6 shadow">
    @csrf
    @include('admin.sekolah._form', ['sekolah' => new \App\Models\Sekolah])
</form>
@endsection