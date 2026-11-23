<?php

namespace Database\Seeders;

use App\Enums\CarBadge;
use App\Enums\CarCondition;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\OptionGroup;
use App\Enums\Transmission;
use App\Models\Brand;
use App\Models\Car;
use App\Models\Category;
use App\Models\Showroom;
use App\Support\CarSlug;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['SUV', 'Sedan', 'MPV', 'Hatchback', 'EV', 'Pickup'])
            ->mapWithKeys(fn (string $name) => [$name => Category::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name])]);

        $brands = collect(['Toyota', 'Honda', 'Hyundai', 'Mitsubishi', 'BYD', 'Wuling', 'Mazda'])
            ->mapWithKeys(fn (string $name) => [$name => Brand::updateOrCreate(['slug' => Str::slug($name)], ['name' => $name])]);

        $showrooms = collect([
            ['Pemecut Motors Jakarta Selatan', 'Jakarta', 'Jl. TB Simatupang No. 18, Jakarta Selatan', '021-5550-1001', -6.2896, 106.8228],
            ['Pemecut Motors Bandung', 'Bandung', 'Jl. Asia Afrika No. 45, Bandung', '022-5550-2002', -6.9218, 107.6071],
            ['Pemecut Motors Surabaya', 'Surabaya', 'Jl. Basuki Rahmat No. 77, Surabaya', '031-5550-3003', -7.2655, 112.7402],
        ])->map(fn (array $s) => Showroom::updateOrCreate(
            ['name' => $s[0]],
            ['city' => $s[1], 'address' => $s[2], 'phone' => $s[3], 'lat' => $s[4], 'lng' => $s[5]],
        ))->values();

        foreach ($this->cars() as $i => $data) {
            $brand = $brands[$data['brand']];
            $existing = Car::withTrashed()
                ->where('brand_id', $brand->id)->where('name', $data['name'])->where('year', $data['year'])
                ->first();

            $car = $existing ?? new Car;
            $car->forceFill([
                'brand_id' => $brand->id,
                'category_id' => $categories[$data['category']]->id,
                'showroom_id' => $showrooms[$i % $showrooms->count()]->id,
                'name' => $data['name'],
                'slug' => $existing instanceof Car ? $existing->slug : CarSlug::for($brand->name, $data['name'], $data['year']),
                'year' => $data['year'],
                'base_price' => $data['price'],
                'fuel_type' => $data['fuel'],
                'transmission' => $data['transmission'] ?? Transmission::Automatic,
                'engine' => $data['engine'],
                'power_hp' => $data['hp'],
                'torque_nm' => $data['torque'],
                'accel_0_100' => $data['accel'],
                'top_speed' => $data['top'],
                'range_km' => $data['range'] ?? null,
                'seats' => $data['seats'],
                'mileage_km' => $data['mileage'] ?? 0,
                'condition' => $data['condition'] ?? CarCondition::New,
                'stock' => $data['stock'],
                'badge' => $data['badge'],
                'status' => $data['status'] ?? CarStatus::Published,
                'description' => $data['description'],
                'hotspots' => $data['hotspots'],
                'is_featured' => $data['featured'] ?? false,
            ])->save();

            if ($car->trashed()) {
                $car->restore();
            }

            $car->colors()->delete();
            $car->options()->delete();
            $car->colors()->createMany($data['colors']);
            $car->options()->createMany($this->options($data['options']));
        }
    }

    /**
     * @param  array<string, array<int, array{0: string, 1: int, 2: string}>>  $options
     * @return list<array{group: OptionGroup, name: string, description: string, price: int}>
     */
    private function options(array $options): array
    {
        $rows = [];
        foreach ($options as $group => $items) {
            foreach ($items as [$name, $price, $description]) {
                $rows[] = ['group' => OptionGroup::from($group), 'name' => $name, 'description' => $description, 'price' => $price];
            }
        }

        return $rows;
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function cars(): array
    {
        $color = fn (string $name, string $hex, int $delta = 0) => ['name' => $name, 'hex' => $hex, 'price_delta' => $delta];
        $juta = 1_000_000;

        $white = $color('Putih Mutiara', '#F5F5F0', 5 * $juta);
        $black = $color('Hitam Metalik', '#1B1B1D');
        $silver = $color('Silver Metalik', '#B8BCC2');
        $grey = $color('Abu-abu Titanium', '#5A5E66', 3 * $juta);
        $red = $color('Merah Marun', '#8B1E2D', 4 * $juta);
        $blue = $color('Biru Safir', '#1F3F7A', 6 * $juta);

        return [
            [
                'brand' => 'Toyota', 'name' => 'Fortuner', 'year' => 2024, 'category' => 'SUV', 'price' => 615 * $juta,
                'fuel' => FuelType::Diesel, 'engine' => '2.4L Turbodiesel 4 Silinder', 'hp' => 148, 'torque' => 400,
                'accel' => 11.5, 'top' => 175, 'seats' => 7, 'stock' => 5, 'badge' => CarBadge::Hot, 'featured' => true,
                'description' => 'SUV tangguh berbasis ladder frame dengan kabin luas tujuh penumpang, cocok untuk keluarga dan perjalanan jauh.',
                'colors' => [$black, $white, $silver],
                'options' => [
                    'wheels' => [['Velg 18 inci Dual-tone', 6 * $juta, 'Velg alloy dua warna'], ['Velg 20 inci Hitam', 11 * $juta, 'Velg alloy hitam gloss']],
                    'interior' => [['Kulit Hitam', 8 * $juta, 'Jok kulit hitam'], ['Kulit Cokelat', 9 * $juta, 'Jok kulit cokelat tan']],
                    'package' => [['Paket Safety', 15 * $juta, 'Kamera 360 dan sensor parkir'], ['Paket Off-road', 18 * $juta, 'Side step dan skid plate']],
                ],
                'hotspots' => [
                    ['x' => 28.0, 'y' => 62.5, 'title' => 'Velg Alloy 18"', 'description' => 'Velg dual-tone dengan ban profil tebal.'],
                    ['x' => 18.5, 'y' => 48.0, 'title' => 'Lampu LED', 'description' => 'Headlamp LED dengan fitur auto-leveling.'],
                    ['x' => 62.0, 'y' => 30.0, 'title' => 'Panoramic Roof', 'description' => 'Atap kaca untuk kabin lebih terang.'],
                ],
            ],
            [
                'brand' => 'Toyota', 'name' => 'Innova Zenix Hybrid', 'year' => 2024, 'category' => 'MPV', 'price' => 495 * $juta,
                'fuel' => FuelType::Hybrid, 'transmission' => Transmission::Cvt, 'engine' => '2.0L Hybrid Dynamic Force',
                'hp' => 186, 'torque' => 188, 'accel' => 9.8, 'top' => 180, 'seats' => 7, 'stock' => 6, 'badge' => CarBadge::New,
                'featured' => true,
                'description' => 'MPV hybrid generasi terbaru dengan konsumsi bahan bakar efisien dan kabin senyap.',
                'colors' => [$white, $silver, $black, $grey],
                'options' => [
                    'wheels' => [['Velg 17 inci Silver', 4 * $juta, 'Velg alloy silver'], ['Velg 18 inci Hitam', 7 * $juta, 'Velg alloy hitam']],
                    'interior' => [['Jok Kulit Hitam', 7 * $juta, 'Jok kulit sintetis hitam'], ['Jok Kulit Krem', 8 * $juta, 'Jok kulit sintetis krem']],
                    'package' => [['Paket Toyota Safety Sense', 12 * $juta, 'Pre-collision dan lane departure'], ['Paket Captain Seat', 10 * $juta, 'Kursi baris kedua captain seat']],
                ],
                'hotspots' => [
                    ['x' => 30.0, 'y' => 63.0, 'title' => 'Mesin Hybrid', 'description' => 'Kombinasi mesin bensin 2.0L dan motor listrik.'],
                    ['x' => 70.0, 'y' => 42.0, 'title' => 'Pintu Geser', 'description' => 'Akses mudah ke baris kedua dan ketiga.'],
                ],
            ],
            [
                'brand' => 'Honda', 'name' => 'Civic RS', 'year' => 2024, 'category' => 'Sedan', 'price' => 597 * $juta,
                'fuel' => FuelType::Petrol, 'transmission' => Transmission::Cvt, 'engine' => '1.5L VTEC Turbo',
                'hp' => 176, 'torque' => 240, 'accel' => 8.9, 'top' => 200, 'seats' => 5, 'stock' => 4, 'badge' => CarBadge::New,
                'featured' => true,
                'description' => 'Sedan sporty dengan mesin turbo responsif dan desain agresif khas varian RS.',
                'colors' => [$white, $black, $red],
                'options' => [
                    'wheels' => [['Velg 18 inci RS', 5 * $juta, 'Velg alloy desain RS'], ['Velg 18 inci Hitam', 7 * $juta, 'Velg alloy hitam glossy']],
                    'interior' => [['Kulit Hitam Merah', 6 * $juta, 'Jok kulit dengan jahitan merah'], ['Kulit Hitam', 5 * $juta, 'Jok kulit hitam polos']],
                    'package' => [['Honda Sensing', 14 * $juta, 'Adaptive cruise dan collision mitigation'], ['Paket Aero', 9 * $juta, 'Body kit dan spoiler']],
                ],
                'hotspots' => [
                    ['x' => 24.0, 'y' => 55.0, 'title' => 'Grille Honeycomb', 'description' => 'Grille hitam gloss khas RS.'],
                    ['x' => 78.0, 'y' => 40.0, 'title' => 'Spoiler Belakang', 'description' => 'Spoiler bibir untuk tampilan sporty.'],
                    ['x' => 45.0, 'y' => 66.0, 'title' => 'Velg 18"', 'description' => 'Velg alloy dengan ban 235/40.'],
                ],
            ],
            [
                'brand' => 'Honda', 'name' => 'Brio RS', 'year' => 2024, 'category' => 'Hatchback', 'price' => 237 * $juta,
                'fuel' => FuelType::Petrol, 'transmission' => Transmission::Cvt, 'engine' => '1.2L i-VTEC',
                'hp' => 90, 'torque' => 110, 'accel' => 12.8, 'top' => 170, 'seats' => 5, 'stock' => 8, 'badge' => CarBadge::New,
                'description' => 'Hatchback kompak lincah untuk kota dengan biaya perawatan terjangkau.',
                'colors' => [$red, $white, $blue, $black],
                'options' => [
                    'wheels' => [['Velg 15 inci Alloy', 2 * $juta, 'Velg alloy standar RS'], ['Velg 16 inci Hitam', 4 * $juta, 'Velg alloy hitam']],
                    'interior' => [['Jok Fabric Oranye', 2 * $juta, 'Jok fabric aksen oranye'], ['Jok Fabric Hitam', 1 * $juta, 'Jok fabric hitam']],
                    'package' => [['Paket Hiburan', 5 * $juta, 'Head unit layar sentuh dan kamera mundur'], ['Paket Aksesori', 3 * $juta, 'Karpet dan pelindung bagasi']],
                ],
                'hotspots' => [
                    ['x' => 25.0, 'y' => 60.0, 'title' => 'Lampu Depan', 'description' => 'Headlamp LED dengan DRL.'],
                    ['x' => 72.0, 'y' => 45.0, 'title' => 'Atap Kontras', 'description' => 'Aksen atap hitam khas RS.'],
                ],
            ],
            [
                'brand' => 'Hyundai', 'name' => 'Ioniq 5', 'year' => 2024, 'category' => 'EV', 'price' => 782 * $juta,
                'fuel' => FuelType::Electric, 'engine' => 'Motor Listrik Tunggal 160 kW',
                'hp' => 217, 'torque' => 350, 'accel' => 7.4, 'top' => 185, 'range' => 451, 'seats' => 5, 'stock' => 3,
                'badge' => CarBadge::Electric, 'featured' => true,
                'description' => 'Crossover listrik dengan platform 800V, pengisian cepat, dan desain pixel yang ikonik.',
                'colors' => [$silver, $black, $grey],
                'options' => [
                    'wheels' => [['Velg 19 inci Aero', 5 * $juta, 'Velg aero efisien'], ['Velg 20 inci', 8 * $juta, 'Velg alloy 20 inci']],
                    'interior' => [['Interior Abu Gelap', 0, 'Kain daur ulang abu gelap'], ['Interior Terang', 6 * $juta, 'Kulit eco warna terang']],
                    'package' => [['Paket Vision Roof', 18 * $juta, 'Atap kaca panoramik'], ['Paket Home Charger', 12 * $juta, 'Wallbox 7 kW dengan instalasi']],
                ],
                'hotspots' => [
                    ['x' => 20.0, 'y' => 58.0, 'title' => 'Lampu Pixel', 'description' => 'Lampu LED bergaya piksel.'],
                    ['x' => 52.0, 'y' => 68.0, 'title' => 'Baterai 72.6 kWh', 'description' => 'Baterai lantai dengan pengisian 10-80% sekitar 18 menit.'],
                    ['x' => 82.0, 'y' => 50.0, 'title' => 'Port Pengisian', 'description' => 'Port CCS2 di sisi belakang.'],
                ],
            ],
            [
                'brand' => 'Hyundai', 'name' => 'Creta', 'year' => 2024, 'category' => 'SUV', 'price' => 389 * $juta,
                'fuel' => FuelType::Petrol, 'transmission' => Transmission::Cvt, 'engine' => '1.5L MPI',
                'hp' => 115, 'torque' => 144, 'accel' => 12.1, 'top' => 175, 'seats' => 5, 'stock' => 7, 'badge' => CarBadge::New,
                'description' => 'SUV kompak dengan fitur lengkap dan kabin modern untuk pemakaian harian.',
                'colors' => [$white, $grey, $red, $black],
                'options' => [
                    'wheels' => [['Velg 17 inci Alloy', 3 * $juta, 'Velg alloy dua warna'], ['Velg 17 inci Hitam', 5 * $juta, 'Velg alloy hitam']],
                    'interior' => [['Jok Kulit Hitam', 5 * $juta, 'Jok kulit sintetis hitam'], ['Jok Kulit Abu', 5 * $juta, 'Jok kulit sintetis abu']],
                    'package' => [['Paket SmartSense', 10 * $juta, 'ADAS level 2'], ['Paket Sunroof', 8 * $juta, 'Sunroof elektrik']],
                ],
                'hotspots' => [
                    ['x' => 22.0, 'y' => 52.0, 'title' => 'Grille Parametrik', 'description' => 'Grille besar dengan pola parametrik.'],
                    ['x' => 60.0, 'y' => 28.0, 'title' => 'Sunroof', 'description' => 'Sunroof elektrik membuka kabin.'],
                    ['x' => 40.0, 'y' => 64.0, 'title' => 'Velg 17"', 'description' => 'Velg alloy dengan desain aerodinamis.'],
                ],
            ],
            [
                'brand' => 'Mitsubishi', 'name' => 'Triton', 'year' => 2024, 'category' => 'Pickup', 'price' => 487 * $juta,
                'fuel' => FuelType::Diesel, 'transmission' => Transmission::Manual, 'engine' => '2.4L MIVEC Turbodiesel',
                'hp' => 184, 'torque' => 470, 'accel' => 10.9, 'top' => 180, 'seats' => 5, 'stock' => 4, 'badge' => CarBadge::New,
                'description' => 'Pickup double cabin bertenaga dengan sistem penggerak 4x4 Super Select untuk medan berat.',
                'colors' => [$white, $black, $silver],
                'options' => [
                    'wheels' => [['Velg 18 inci Alloy', 4 * $juta, 'Velg alloy hitam'], ['Velg 17 inci All-terrain', 6 * $juta, 'Velg dengan ban AT']],
                    'interior' => [['Jok Kulit Hitam', 6 * $juta, 'Jok kulit hitam'], ['Jok Fabric Premium', 2 * $juta, 'Jok fabric tahan noda']],
                    'package' => [['Paket Bak', 9 * $juta, 'Hardcover dan liner bak'], ['Paket Off-road', 14 * $juta, 'Winch dan snorkel']],
                ],
                'hotspots' => [
                    ['x' => 20.0, 'y' => 55.0, 'title' => 'Dynamic Shield', 'description' => 'Desain depan kokoh khas Mitsubishi.'],
                    ['x' => 76.0, 'y' => 50.0, 'title' => 'Bak Terbuka', 'description' => 'Kapasitas angkut hingga satu ton.'],
                ],
            ],
            [
                'brand' => 'BYD', 'name' => 'Seal', 'year' => 2024, 'category' => 'EV', 'price' => 719 * $juta,
                'fuel' => FuelType::Electric, 'engine' => 'Motor Listrik Tunggal 230 kW',
                'hp' => 313, 'torque' => 360, 'accel' => 5.9, 'top' => 180, 'range' => 650, 'seats' => 5, 'stock' => 3,
                'badge' => CarBadge::Electric, 'featured' => true,
                'description' => 'Sedan listrik performa tinggi dengan baterai Blade dan jarak tempuh lebih dari 600 km.',
                'colors' => [$grey, $blue, $white],
                'options' => [
                    'wheels' => [['Velg 19 inci Sport', 6 * $juta, 'Velg sport 19 inci'], ['Velg 18 inci Aero', 3 * $juta, 'Velg aero efisien']],
                    'interior' => [['Kulit Hitam', 5 * $juta, 'Jok kulit vegan hitam'], ['Kulit Biru', 7 * $juta, 'Jok kulit vegan biru']],
                    'package' => [['Paket Premium Audio', 10 * $juta, 'Audio Dynaudio'], ['Paket Home Charger', 12 * $juta, 'Wallbox 7 kW dengan instalasi']],
                ],
                'hotspots' => [
                    ['x' => 18.0, 'y' => 56.0, 'title' => 'Lampu LED', 'description' => 'Lampu depan LED ramping.'],
                    ['x' => 50.0, 'y' => 66.0, 'title' => 'Blade Battery', 'description' => 'Baterai LFP aman dan awet.'],
                    ['x' => 55.0, 'y' => 28.0, 'title' => 'Atap Kaca', 'description' => 'Atap kaca panoramik.'],
                ],
            ],
            [
                'brand' => 'Wuling', 'name' => 'Binguo EV', 'year' => 2024, 'category' => 'EV', 'price' => 349 * $juta,
                'fuel' => FuelType::Electric, 'engine' => 'Motor Listrik 50 kW',
                'hp' => 68, 'torque' => 150, 'accel' => 13.0, 'top' => 130, 'range' => 333, 'seats' => 5, 'stock' => 8,
                'badge' => CarBadge::Electric,
                'description' => 'Mobil listrik perkotaan yang mungil, ramah dompet, dan mudah diparkir.',
                'colors' => [$color('Hijau Mint', '#9CCFB5', 2 * $juta), $white, $color('Kuning Pastel', '#F2D27A')],
                'options' => [
                    'wheels' => [['Velg 15 inci Alloy', 3 * $juta, 'Velg alloy 15 inci'], ['Velg 15 inci Dua Warna', 4 * $juta, 'Velg dua warna']],
                    'interior' => [['Jok Dua Warna', 2 * $juta, 'Jok dua warna'], ['Jok Polos Krem', 1 * $juta, 'Jok krem polos']],
                    'package' => [['Paket Charger Rumah', 8 * $juta, 'Wallbox 3.3 kW dengan instalasi'], ['Paket Kamera', 4 * $juta, 'Kamera 360 derajat']],
                ],
                'hotspots' => [
                    ['x' => 25.0, 'y' => 50.0, 'title' => 'Lampu Bulat', 'description' => 'Headlamp bulat dengan DRL.'],
                    ['x' => 66.0, 'y' => 62.0, 'title' => 'Port Pengisian', 'description' => 'Port pengisian di bagian depan.'],
                ],
            ],
            [
                'brand' => 'Mazda', 'name' => 'CX-5', 'year' => 2021, 'category' => 'SUV', 'price' => 425 * $juta,
                'fuel' => FuelType::Petrol, 'engine' => '2.5L SkyActiv-G',
                'hp' => 188, 'torque' => 252, 'accel' => 9.5, 'top' => 200, 'seats' => 5, 'stock' => 1, 'badge' => CarBadge::Verified,
                'condition' => CarCondition::Used, 'mileage' => 38_000, 'status' => CarStatus::Draft,
                'description' => 'CX-5 bekas terawat, service record lengkap, dan sudah melalui inspeksi 150 titik.',
                'colors' => [$red, $grey],
                'options' => [
                    'wheels' => [['Velg Original 19 inci', 0, 'Velg standar pabrik'], ['Velg Aftermarket 19 inci', 6 * $juta, 'Velg alloy pilihan']],
                    'interior' => [['Kulit Cokelat Original', 0, 'Jok kulit cokelat'], ['Cover Premium', 3 * $juta, 'Cover jok kulit premium']],
                    'package' => [['Garansi Perpanjangan 1 Tahun', 8 * $juta, 'Garansi mesin dan transmisi'], ['Paket Servis Berkala', 5 * $juta, 'Servis dua kali dalam setahun']],
                ],
                'hotspots' => [
                    ['x' => 22.0, 'y' => 54.0, 'title' => 'Grille Signature Wing', 'description' => 'Grille khas Mazda KODO.'],
                    ['x' => 62.0, 'y' => 60.0, 'title' => 'Kondisi Bodi', 'description' => 'Cat original tanpa repaint.'],
                ],
            ],
        ];
    }
}
