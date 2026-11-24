<?php

namespace App\Http\Requests\Dashboard;

use App\Models\Showroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ShowroomRequest extends FormRequest
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
                'max:80',
                Rule::unique(Showroom::class, 'name')->ignore($this->showroomId()),
            ],
            'city' => ['required', 'string', 'max:60'],
            'address' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'regex:/^[0-9+\-\s()]{6,20}$/'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama showroom wajib diisi.',
            'name.max' => 'Nama showroom maksimal 80 karakter.',
            'name.unique' => 'Nama showroom sudah dipakai.',
            'city.required' => 'Kota wajib diisi.',
            'city.max' => 'Kota maksimal 60 karakter.',
            'address.required' => 'Alamat wajib diisi.',
            'address.max' => 'Alamat maksimal 255 karakter.',
            'phone.required' => 'Nomor telepon wajib diisi.',
            'phone.regex' => 'Nomor telepon harus 6-20 karakter berisi angka, spasi, +, -, atau tanda kurung.',
            'lat.required' => 'Latitude wajib diisi.',
            'lat.numeric' => 'Latitude harus berupa angka.',
            'lat.between' => 'Latitude harus di antara -90 dan 90.',
            'lng.required' => 'Longitude wajib diisi.',
            'lng.numeric' => 'Longitude harus berupa angka.',
            'lng.between' => 'Longitude harus di antara -180 dan 180.',
        ];
    }

    private function showroomId(): ?int
    {
        $showroom = $this->route('showroom');

        return $showroom instanceof Showroom ? $showroom->id : null;
    }
}
