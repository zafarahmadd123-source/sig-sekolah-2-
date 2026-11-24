<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Dinas = 'dinas';
    case Sekolah = 'sekolah';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Dinas => 'Dinas Pendidikan',
            self::Sekolah => 'Sekolah',
        };
    }
}
