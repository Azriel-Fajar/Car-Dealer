<?php

namespace App\Enums;

use App\Enums\Concerns\HasOptions;

enum CarStatus: string
{
    use HasOptions;

    case Draft = 'draft';
    case Published = 'published';
    case SoldOut = 'sold_out';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Published => 'Tayang',
            self::SoldOut => 'Terjual habis',
        };
    }
}
