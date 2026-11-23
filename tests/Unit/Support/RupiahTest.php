<?php

namespace Tests\Unit\Support;

use App\Support\Rupiah;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class RupiahTest extends TestCase
{
    /**
     * @return array<string, array{int, string, string}>
     */
    public static function vectors(): array
    {
        return [
            '0' => [0, 'Rp 0', 'Rp 0'],
            '1500' => [1500, 'Rp 1.500', 'Rp 1.500'],
            '950000' => [950000, 'Rp 950.000', 'Rp 950.000'],
            '1000000' => [1000000, 'Rp 1.000.000', 'Rp 1 jt'],
            '9750000' => [9750000, 'Rp 9.750.000', 'Rp 9,8 jt'],
            '9840000' => [9840000, 'Rp 9.840.000', 'Rp 9,8 jt'],
            '10000000' => [10000000, 'Rp 10.000.000', 'Rp 10 jt'],
            '485000000' => [485000000, 'Rp 485.000.000', 'Rp 485 jt'],
            '999960000' => [999960000, 'Rp 999.960.000', 'Rp 1 M'],
            '1250000000' => [1250000000, 'Rp 1.250.000.000', 'Rp 1,3 M'],
            '2500000000000' => [2500000000000, 'Rp 2.500.000.000.000', 'Rp 2.500 M'],
            '-1500' => [-1500, '-Rp 1.500', '-Rp 1.500'],
            '-9750000' => [-9750000, '-Rp 9.750.000', '-Rp 9,8 jt'],
        ];
    }

    #[DataProvider('vectors')]
    public function test_format(int $amount, string $format, string $short): void
    {
        $this->assertSame($format, Rupiah::format($amount));
    }

    #[DataProvider('vectors')]
    public function test_short(int $amount, string $format, string $short): void
    {
        $this->assertSame($short, Rupiah::short($amount));
    }
}
