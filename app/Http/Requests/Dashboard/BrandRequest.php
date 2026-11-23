<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Brand;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class BrandRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:60',
                Rule::unique(Brand::class, 'name')->ignore($this->brandId()),
            ],
            'logo' => ['nullable', 'image', 'mimes:png,webp', 'max:1024'],
        ];
    }

    /**
     * Refuse names whose slug collides with another brand (e.g. "Land-Rover" vs "Land Rover").
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('name')) {
                    return;
                }

                $slug = Str::slug($this->string('name')->toString());

                $taken = $slug === '' || Brand::query()
                    ->where('slug', $slug)
                    ->when($this->brandId(), fn ($query, int $id) => $query->whereKeyNot($id))
                    ->exists();

                if ($taken) {
                    $validator->errors()->add('name', 'Nama brand ini tidak bisa dipakai karena bentrok dengan brand lain.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama brand wajib diisi.',
            'name.max' => 'Nama brand maksimal 60 karakter.',
            'name.unique' => 'Nama brand sudah dipakai.',
            'logo.image' => 'Logo harus berupa gambar PNG atau WebP.',
            'logo.mimes' => 'Logo harus berupa gambar PNG atau WebP.',
            'logo.max' => 'Ukuran logo maksimal 1 MB.',
        ];
    }

    private function brandId(): ?int
    {
        $brand = $this->route('brand');

        return $brand instanceof Brand ? $brand->id : null;
    }
}
