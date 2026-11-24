<?php

namespace App\Http\Requests\Dashboard;

use App\Enums\CarBadge;
use App\Enums\CarCondition;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\Transmission;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Showroom;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CarRequest extends FormRequest
{
    public const DRAFT_ONLY_MESSAGE = 'Simpan sebagai draft dulu, tambahkan foto & warna sebelum tayang.';

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $maxYear = (int) now()->year + 1;

        return [
            'brand_id' => ['required', 'integer', Rule::exists(Brand::class, 'id')],
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'showroom_id' => ['nullable', 'integer', Rule::exists(Showroom::class, 'id')],
            'name' => ['required', 'string', 'max:80'],
            'year' => ['required', 'integer', "between:1990,{$maxYear}"],
            'base_price' => ['required', 'integer', 'min:10000000', 'max:50000000000'],
            'fuel_type' => ['required', Rule::enum(FuelType::class)],
            'transmission' => ['required', Rule::enum(Transmission::class)],
            'condition' => ['required', Rule::enum(CarCondition::class)],
            // A new car has no photos or colors yet, so it can only start as a draft.
            'status' => $this->car() === null
                ? ['required', Rule::in([CarStatus::Draft->value])]
                : ['required', Rule::enum(CarStatus::class)],
            'badge' => ['nullable', Rule::enum(CarBadge::class)],
            'engine' => ['nullable', 'string', 'max:40'],
            'power_hp' => ['required', 'integer', 'between:1,2000'],
            'torque_nm' => ['required', 'integer', 'between:1,3000'],
            'top_speed' => ['required', 'integer', 'between:1,500'],
            'seats' => ['required', 'integer', 'between:2,9'],
            'stock' => ['required', 'integer', 'between:0,999'],
            'mileage_km' => ['required', 'integer', 'between:0,2000000'],
            'range_km' => ['nullable', 'integer', 'between:1,2000', 'required_if:fuel_type,'.FuelType::Electric->value],
            'accel_0_100' => ['nullable', 'numeric', 'between:1,30'],
            'description' => ['nullable', 'string', 'max:5000'],
            'is_featured' => ['boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($this->input('condition') === CarCondition::New->value && (int) $this->input('mileage_km') > 100) {
                    $validator->errors()->add('mileage_km', 'Mobil baru maksimal 100 km.');
                }

                $car = $this->car();

                if ($car !== null && $this->input('status') === CarStatus::Published->value
                    && (! $car->images()->exists() || ! $car->colors()->exists())) {
                    $validator->errors()->add('status', 'Tambahkan minimal 1 foto dan 1 warna sebelum menayangkan mobil.');
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
            'brand_id.required' => 'Brand wajib dipilih.',
            'brand_id.exists' => 'Brand tidak ditemukan.',
            'category_id.required' => 'Kategori wajib dipilih.',
            'category_id.exists' => 'Kategori tidak ditemukan.',
            'showroom_id.exists' => 'Showroom tidak ditemukan.',
            'name.required' => 'Nama mobil wajib diisi.',
            'name.max' => 'Nama mobil maksimal 80 karakter.',
            'year.required' => 'Tahun wajib diisi.',
            'year.integer' => 'Tahun harus berupa angka.',
            'year.between' => 'Tahun harus di antara :min dan :max.',
            'base_price.required' => 'Harga wajib diisi.',
            'base_price.integer' => 'Harga harus berupa angka.',
            'base_price.min' => 'Harga minimal Rp 10.000.000.',
            'base_price.max' => 'Harga maksimal Rp 50.000.000.000.',
            'fuel_type.required' => 'Bahan bakar wajib dipilih.',
            'fuel_type.enum' => 'Bahan bakar tidak valid.',
            'transmission.required' => 'Transmisi wajib dipilih.',
            'transmission.enum' => 'Transmisi tidak valid.',
            'condition.required' => 'Kondisi wajib dipilih.',
            'condition.enum' => 'Kondisi tidak valid.',
            'status.required' => 'Status wajib dipilih.',
            'status.in' => self::DRAFT_ONLY_MESSAGE,
            'status.enum' => 'Status tidak valid.',
            'badge.enum' => 'Badge tidak valid.',
            'engine.max' => 'Mesin maksimal 40 karakter.',
            'power_hp.required' => 'Tenaga wajib diisi.',
            'power_hp.integer' => 'Tenaga harus berupa angka bulat.',
            'power_hp.between' => 'Tenaga harus di antara :min dan :max hp.',
            'torque_nm.required' => 'Torsi wajib diisi.',
            'torque_nm.integer' => 'Torsi harus berupa angka bulat.',
            'torque_nm.between' => 'Torsi harus di antara :min dan :max Nm.',
            'top_speed.required' => 'Kecepatan maksimum wajib diisi.',
            'top_speed.integer' => 'Kecepatan maksimum harus berupa angka bulat.',
            'top_speed.between' => 'Kecepatan maksimum harus di antara :min dan :max km/j.',
            'seats.required' => 'Jumlah kursi wajib diisi.',
            'seats.integer' => 'Jumlah kursi harus berupa angka bulat.',
            'seats.between' => 'Jumlah kursi harus di antara :min dan :max.',
            'stock.required' => 'Stok wajib diisi.',
            'stock.integer' => 'Stok harus berupa angka bulat.',
            'stock.between' => 'Stok harus di antara :min dan :max.',
            'mileage_km.required' => 'Jarak tempuh wajib diisi.',
            'mileage_km.integer' => 'Jarak tempuh harus berupa angka bulat.',
            'mileage_km.between' => 'Jarak tempuh harus di antara 0 dan 2.000.000 km.',
            'range_km.required_if' => 'Jarak tempuh baterai wajib diisi untuk mobil listrik.',
            'range_km.integer' => 'Jarak tempuh baterai harus berupa angka bulat.',
            'range_km.between' => 'Jarak tempuh baterai harus di antara :min dan :max km.',
            'accel_0_100.numeric' => 'Akselerasi harus berupa angka.',
            'accel_0_100.between' => 'Akselerasi 0–100 harus di antara :min dan :max detik.',
            'description.max' => 'Deskripsi maksimal 5000 karakter.',
            'is_featured.boolean' => 'Nilai unggulan tidak valid.',
        ];
    }

    private function car(): ?Car
    {
        $car = $this->route('car');

        return $car instanceof Car ? $car : null;
    }
}
