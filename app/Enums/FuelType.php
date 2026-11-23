<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum FuelType: string
{
    use HasOptions;

    case Petrol = 'petrol';
    case Diesel = 'diesel';
    case Hybrid = 'hybrid';
    case Electric = 'electric';

    public function label(): string
    {
        return match ($this) {
            self::Petrol => 'Bensin',
            self::Diesel => 'Diesel',
            self::Hybrid => 'Hybrid',
            self::Electric => 'Listrik',
        };
    }
}
