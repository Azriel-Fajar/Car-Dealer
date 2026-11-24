<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Car;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderCarImagesRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ];
    }

    /**
     * The new order must name every photo of this car exactly once.
     *
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Car $car */
                $car = $this->route('car');
                $expected = $car->images()->pluck('id')->map(fn ($id): int => (int) $id)->sort()->values()->all();
                $given = collect($this->ids())->sort()->values()->all();

                if ($given !== $expected) {
                    $validator->errors()->add('ids', 'Urutan foto tidak sesuai dengan foto mobil ini. Muat ulang halaman lalu coba lagi.');
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
            'ids.required' => 'Urutan foto wajib dikirim.',
            'ids.array' => 'Urutan foto tidak valid.',
            'ids.*.integer' => 'Urutan foto tidak valid.',
        ];
    }

    /**
     * @return list<int>
     */
    public function ids(): array
    {
        return array_values(array_map(intval(...), (array) $this->input('ids')));
    }
}
