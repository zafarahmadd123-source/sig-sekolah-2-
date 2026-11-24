<?php

namespace App\Http\Controllers;

use App\Models\Sekolah;
use Illuminate\View\View;

class SekolahPublikController extends Controller
{
    public function show(Sekolah $sekolah): View
    {
        $sekolah->loadMissing('profil');

        return view('sekolah.public', compact('sekolah'));
    }
}
