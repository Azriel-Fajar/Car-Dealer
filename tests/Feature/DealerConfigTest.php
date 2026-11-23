<?php

namespace Tests\Feature;

use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class DealerConfigTest extends TestCase
{
    public function test_dealer_config_matches_spec(): void
    {
        $this->assertSame('Pemecut Motors', config('dealer.brand_name'));
        $this->assertSame(5_000_000, config('dealer.booking_fee'));
        $this->assertSame(24, config('dealer.payment_due_hours'));
        $this->assertSame(0.05, config('dealer.credit.flat_interest_rate'));
        $this->assertSame([20, 25, 30, 40, 50], config('dealer.credit.down_payment_percents'));
        $this->assertSame([12, 24, 36, 48, 60], config('dealer.credit.tenor_months'));
        $this->assertCount(3, config('dealer.bank_accounts'));
        foreach (config('dealer.bank_accounts') as $acc) {
            $this->assertSame(['bank', 'account_number', 'account_name'], array_keys($acc));
        }
    }

    public function test_brand_name_is_shared_with_inertia(): void
    {
        $this->get(route('home'))->assertInertia(fn (Assert $p) => $p->where('name', 'Pemecut Motors'));
    }
}
