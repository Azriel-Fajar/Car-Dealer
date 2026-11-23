<?php

namespace App\Support;

final class Rupiah
{
    public static function format(int $amount): string
    {
        return self::sign($amount).'Rp '.self::group(abs($amount));
    }

    public static function short(int $amount): string
    {
        $a = abs($amount);

        if ($a < 1_000_000) {
            return self::format($amount);
        }

        $unit = $a >= 1_000_000_000 ? 1_000_000_000 : 1_000_000;
        $tenths = intdiv($a * 10 + intdiv($unit, 2), $unit);

        if ($unit === 1_000_000 && $tenths >= 10_000) {
            $unit = 1_000_000_000;
            $tenths = intdiv($a * 10 + intdiv($unit, 2), $unit);
        }

        $text = self::group(intdiv($tenths, 10));
        if ($tenths % 10 !== 0) {
            $text .= ','.($tenths % 10);
        }

        return self::sign($amount).'Rp '.$text.' '.($unit === 1_000_000_000 ? 'M' : 'jt');
    }

    private static function sign(int $amount): string
    {
        return $amount < 0 ? '-' : '';
    }

    private static function group(int $n): string
    {
        return number_format($n, 0, ',', '.');
    }
}
