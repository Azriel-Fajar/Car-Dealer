<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Car;
use App\Models\CarColor;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarColorRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var Car $car */
        $car = $this->route('car');
        $color = $this->route('color');

        return [
            'name' => [
                'required',
                'string',
                'max:40',
                Rule::unique(CarColor::class, 'name')
                    ->where('car_id', $car->id)
                    ->ignore($color instanceof CarColor ? $color->id : null),
            ],
            'hex' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'price_delta' => ['required', 'integer', 'between:-100000000,500000000'],
            'image' => ['nullable', ...CatalogImage::RULES],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama warna wajib diisi.',
            'name.max' => 'Nama warna maksimal 40 karakter.',
            'name.unique' => 'Mobil ini sudah punya warna dengan nama tersebut.',
            'hex.required' => 'Kode warna wajib diisi.',
            'hex.regex' => 'Kode warna harus berformat #RRGGBB.',
            'price_delta.required' => 'Selisih harga wajib diisi.',
            'price_delta.integer' => 'Selisih harga harus berupa angka bulat.',
            'price_delta.between' => 'Selisih harga harus di antara -Rp 100.000.000 dan Rp 500.000.000.',
            ...CatalogImage::messages('image'),
        ];
    }
}
