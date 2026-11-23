<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CarCondition: string
{
    use HasOptions;

    case New = 'new';
    case Used = 'used';

    public function label(): string
    {
        return match ($this) {
            self::New => 'Baru',
            self::Used => 'Bekas',
        };
    }
}
