<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CarBadge: string
{
    use HasOptions;

    case New = 'new';
    case Verified = 'verified';
    case Electric = 'electric';
    case Classic = 'classic';
    case Hot = 'hot';

    public function label(): string
    {
        return match ($this) {
            self::New => 'NEW',
            self::Verified => 'VERIFIED',
            self::Electric => 'ELECTRIC',
            self::Classic => 'CLASSIC',
            self::Hot => 'HOT',
        };
    }
}
