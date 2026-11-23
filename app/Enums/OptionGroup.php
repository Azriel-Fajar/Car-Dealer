<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum OptionGroup: string
{
    use HasOptions;

    case Wheels = 'wheels';
    case Interior = 'interior';
    case Package = 'package';

    public function label(): string
    {
        return match ($this) {
            self::Wheels => 'Velg',
            self::Interior => 'Interior',
            self::Package => 'Paket',
        };
    }
}
