<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CategoryRequest extends FormRequest
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
                'max:40',
                Rule::unique(Category::class, 'name')->ignore($this->categoryId()),
            ],
        ];
    }

    /**
     * Refuse names whose slug collides with another category (e.g. "SUV!" vs "SUV").
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

                $taken = $slug === '' || Category::query()
                    ->where('slug', $slug)
                    ->when($this->categoryId(), fn ($query, int $id) => $query->whereKeyNot($id))
                    ->exists();

                if ($taken) {
                    $validator->errors()->add('name', 'Nama kategori ini tidak bisa dipakai karena bentrok dengan kategori lain.');
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
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 40 karakter.',
            'name.unique' => 'Nama kategori sudah dipakai.',
        ];
    }

    private function categoryId(): ?int
    {
        $category = $this->route('category');

        return $category instanceof Category ? $category->id : null;
    }
}
