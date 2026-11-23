<?php

namespace App\Support;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;

final class Media
{
    public static function disk(): Filesystem
    {
        return Storage::disk(config('dealer.media_disk', 'public'));
    }

    public static function url(?string $path): ?string
    {
        return $path === null ? null : self::disk()->url($path);
    }

    public static function delete(?string $path): void
    {
        if ($path !== null) {
            self::disk()->delete($path);
        }
    }
}
