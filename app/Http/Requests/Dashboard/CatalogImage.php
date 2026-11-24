<?php

namespace App\Http\Requests\Dashboard;

/**
 * Upload rules shared by car photos, color images and option images.
 * `image` + `mimes` sniff the real content type, so an SVG renamed to .png is refused.
 */
final class CatalogImage
{
    public const RULES = ['image', 'mimes:jpg,jpeg,png,webp', 'max:4096'];

    /**
     * @return array<string, string>
     */
    public static function messages(string $field): array
    {
        return [
            "{$field}.image" => 'File harus berupa gambar JPG, PNG, atau WebP.',
            "{$field}.mimes" => 'File harus berupa gambar JPG, PNG, atau WebP.',
            "{$field}.max" => 'Ukuran gambar maksimal 4 MB.',
            "{$field}.uploaded" => 'Gambar gagal diunggah. Pastikan ukurannya maksimal 4 MB.',
        ];
    }
}
