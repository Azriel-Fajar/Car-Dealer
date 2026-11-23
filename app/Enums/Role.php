<?php

namespace App\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Sales = 'sales';
    case Customer = 'customer';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Sales => 'Sales',
            self::Customer => 'Pelanggan',
        };
    }

    public function isStaff(): bool
    {
        return $this === self::Admin || $this === self::Sales;
    }
}
