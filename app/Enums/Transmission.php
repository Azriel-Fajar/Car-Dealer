<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum Transmission: string
{
    use HasOptions;

    case Manual = 'manual';
    case Automatic = 'automatic';
    case Cvt = 'cvt';
    case Dct = 'dct';

    public function label(): string
    {
        return match ($this) {
            self::Manual => 'Manual',
            self::Automatic => 'Otomatis',
            self::Cvt => 'CVT',
            self::Dct => 'DCT',
        };
    }
}
