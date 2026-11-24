<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\OptionGroup;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarOptionRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'group' => ['required', Rule::enum(OptionGroup::class)],
            'name' => ['required', 'string', 'max:60'],
            'description' => ['nullable', 'string', 'max:300'],
            'price' => ['required', 'integer', 'between:0,500000000'],
            'image' => ['nullable', ...CatalogImage::RULES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'group.required' => 'Grup opsi wajib dipilih.',
            'group.enum' => 'Grup opsi tidak valid.',
            'name.required' => 'Nama opsi wajib diisi.',
            'name.max' => 'Nama opsi maksimal 60 karakter.',
            'description.max' => 'Deskripsi maksimal 300 karakter.',
            'price.required' => 'Harga opsi wajib diisi.',
            'price.integer' => 'Harga opsi harus berupa angka bulat.',
            'price.between' => 'Harga opsi harus di antara Rp 0 dan Rp 500.000.000.',
            ...CatalogImage::messages('image'),
        ];
    }
}
