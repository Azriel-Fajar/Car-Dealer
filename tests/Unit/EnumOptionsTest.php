<?php

namespace Tests\Unit;

use App\Enums\CarBadge;
use App\Enums\CarCondition;
use App\Enums\CarStatus;
use App\Enums\FuelType;
use App\Enums\OptionGroup;
use App\Enums\Transmission;
use PHPUnit\Framework\TestCase;

class EnumOptionsTest extends TestCase
{
    public function test_fuel_type_options_are_value_label_pairs(): void
    {
        $options = FuelType::options();

        $this->assertCount(4, $options);
        $this->assertSame(['value' => 'petrol', 'label' => 'Bensin'], $options[0]);
    }

    public function test_labels_are_indonesian(): void
    {
        $labels = fn (string $enum): array => array_column($enum::options(), 'label');

        $this->assertSame(['Bensin', 'Diesel', 'Hybrid', 'Listrik'], $labels(FuelType::class));
        $this->assertSame(['Manual', 'Otomatis', 'CVT', 'DCT'], $labels(Transmission::class));
        $this->assertSame(['Baru', 'Bekas'], $labels(CarCondition::class));
        $this->assertSame(['Velg', 'Interior', 'Paket'], $labels(OptionGroup::class));
        $this->assertSame(['Draft', 'Tayang', 'Terjual habis'], $labels(CarStatus::class));
        $this->assertSame(['NEW', 'VERIFIED', 'ELECTRIC', 'CLASSIC', 'HOT'], $labels(CarBadge::class));
    }
}
