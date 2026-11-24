<?php

namespace App\Http\Requests\Dashboard;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class CarHotspotRequest extends FormRequest
{
    public const MAX = 8;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'hotspots' => ['present', 'array', 'max:'.self::MAX],
            'hotspots.*' => ['array:x,y,title,description'],
            'hotspots.*.x' => ['required', 'numeric', 'between:0,100'],
            'hotspots.*.y' => ['required', 'numeric', 'between:0,100'],
            'hotspots.*.title' => ['required', 'string', 'max:40'],
            'hotspots.*.description' => ['required', 'string', 'max:200'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hotspots.present' => 'Daftar hotspot wajib dikirim.',
            'hotspots.array' => 'Daftar hotspot tidak valid.',
            'hotspots.max' => 'Maksimal '.self::MAX.' hotspot per mobil.',
            'hotspots.*.array' => 'Data hotspot tidak valid.',
            'hotspots.*.x.required' => 'Posisi X wajib diisi.',
            'hotspots.*.x.numeric' => 'Posisi X harus berupa angka.',
            'hotspots.*.x.between' => 'Posisi X harus di antara 0 dan 100.',
            'hotspots.*.y.required' => 'Posisi Y wajib diisi.',
            'hotspots.*.y.numeric' => 'Posisi Y harus berupa angka.',
            'hotspots.*.y.between' => 'Posisi Y harus di antara 0 dan 100.',
            'hotspots.*.title.required' => 'Judul hotspot wajib diisi.',
            'hotspots.*.title.max' => 'Judul hotspot maksimal 40 karakter.',
            'hotspots.*.description.required' => 'Deskripsi hotspot wajib diisi.',
            'hotspots.*.description.max' => 'Deskripsi hotspot maksimal 200 karakter.',
        ];
    }

    /**
     * Validated hotspots with x/y rounded to one decimal (the stored shape).
     *
     * @return list<array{x: float, y: float, title: string, description: string}>
     */
    public function hotspots(): array
    {
        /** @var array<int, array{x: float|int|string, y: float|int|string, title: string, description: string}> $hotspots */
        $hotspots = $this->validated('hotspots');

        return array_values(array_map(fn (array $hotspot): array => [
            'x' => round((float) $hotspot['x'], 1),
            'y' => round((float) $hotspot['y'], 1),
            'title' => $hotspot['title'],
            'description' => $hotspot['description'],
        ], $hotspots));
    }
}
