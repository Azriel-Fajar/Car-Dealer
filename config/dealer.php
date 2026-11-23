<?php

return [
    'brand_name' => 'Pemecut Motors',
    'media_disk' => env('MEDIA_DISK', 'public'),
    'booking_fee' => 5_000_000,
    'payment_due_hours' => 24,
    'bank_accounts' => [
        ['bank' => 'BCA', 'account_number' => '8730123456', 'account_name' => 'PT Pemecut Motors Indonesia'],
        ['bank' => 'Mandiri', 'account_number' => '1370012345678', 'account_name' => 'PT Pemecut Motors Indonesia'],
        ['bank' => 'BNI', 'account_number' => '0912345678', 'account_name' => 'PT Pemecut Motors Indonesia'],
    ],
    'credit' => [
        'flat_interest_rate' => 0.05,
        'down_payment_percents' => [20, 25, 30, 40, 50],
        'tenor_months' => [12, 24, 36, 48, 60],
    ],
];
