<?php

namespace Database\Seeders;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Standard License - Monthly',
                'slug' => 'monthly',
                'paystack_plan_code' => 'PLN_8vlerxzzdu0khcm',
                'price' => 156000,
                'currency' => 'ZAR',
                'interval' => 'monthly',
                'interval_months' => 1,
                'monthly_equivalent' => 156000,
                'discount_percent' => 0,
                'sort_order' => 1,
            ],
            [
                'name' => 'Standard License - Bi-Annual',
                'slug' => 'biannual',
                'paystack_plan_code' => 'PLN_mf7rs1le381oovp',
                'price' => 615000,
                'currency' => 'ZAR',
                'interval' => 'biannually',
                'interval_months' => 6,
                'monthly_equivalent' => 102500,
                'discount_percent' => 34,
                'sort_order' => 2,
            ],
            [
                'name' => 'Standard License - Annual',
                'slug' => 'annual',
                'paystack_plan_code' => 'PLN_dm3wysgotrdyuaj',
                'price' => 1000000,
                'currency' => 'ZAR',
                'interval' => 'annually',
                'interval_months' => 12,
                'monthly_equivalent' => 83333,
                'discount_percent' => 47,
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                ['slug' => $plan['slug']],
                $plan,
            );
        }
    }
}
