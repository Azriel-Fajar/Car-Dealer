<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreCarImagesRequest extends FormRequest
{
    public const MAX_PER_REQUEST = 10;

    public const MAX_PER_CAR = 20;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'images' => ['required', 'array', 'min:1', 'max:'.self::MAX_PER_REQUEST],
            'images.*' => ['required', ...CatalogImage::RULES],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('images')) {
                    return;
                }

                /** @var Car $car */
                $car = $this->route('car');
                $remaining = self::MAX_PER_CAR - $car->images()->count();

                if (count((array) $this->file('images')) > $remaining) {
                    $validator->errors()->add(
                        'images',
                        'Maksimal '.self::MAX_PER_CAR." foto per mobil. Sisa slot: {$remaining}.",
                    );
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
            'images.required' => 'Pilih minimal 1 foto.',
            'images.array' => 'Pilih minimal 1 foto.',
            'images.min' => 'Pilih minimal 1 foto.',
            'images.max' => 'Maksimal '.self::MAX_PER_REQUEST.' foto per unggahan.',
            'images.*.required' => 'Foto gagal diunggah.',
            ...CatalogImage::messages('images.*'),
        ];
    }
}
