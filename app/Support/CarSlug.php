<?php

namespace App\Support;

use App\Models\Car;
use Illuminate\Support\Str;

class CarSlug
{
    public static function for(string $brandName, string $name, int $year, ?int $ignoreId = null): string
    {
        $base = Str::slug("{$brandName} {$name} {$year}");
        $slug = $base;
        $suffix = 2;

        while (self::taken($slug, $ignoreId)) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private static function taken(string $slug, ?int $ignoreId): bool
    {
        return Car::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId !== null, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
}
