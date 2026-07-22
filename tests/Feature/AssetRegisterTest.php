<?php

namespace Tests\Feature;

use App\Models\Asset;
use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssetRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function company(User $user): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Asset Co',
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12,
        ]);
    }

    public function test_register_index_renders(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)
            ->get(route('companies.assets.index', $company))
            ->assertOk()
            ->assertSee('Asset Register');
    }

    public function test_can_add_edit_and_remove_an_asset(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)->post(route('companies.assets.store', $company), [
            'name' => 'Delivery Van',
            'acquisition_date' => '2025-01-01',
            'cost' => 200000,
            'residual_value' => 20000,
            'useful_life_years' => 5,
        ])->assertRedirect(route('companies.assets.index', $company));

        $asset = $company->assets()->firstOrFail();
        $this->assertSame('Delivery Van', $asset->name);

        // Straight-line: (200000 - 20000)/5 = 36000 per year; one full year to 2026-01-01.
        $this->assertEqualsWithDelta(36000.0, $asset->accumulatedDepreciation('2026-01-01'), 0.5);
        $this->assertEqualsWithDelta(164000.0, $asset->netBookValue('2026-01-01'), 0.5);

        $this->actingAs($user)->patch(route('companies.assets.update', [$company, $asset]), [
            'name' => 'Delivery Van (renamed)',
            'acquisition_date' => '2025-01-01',
            'cost' => 200000,
        ])->assertRedirect(route('companies.assets.index', $company));

        $this->assertSame('Delivery Van (renamed)', $asset->fresh()->name);

        $this->actingAs($user)->delete(route('companies.assets.destroy', [$company, $asset]))
            ->assertRedirect(route('companies.assets.index', $company));

        $this->assertDatabaseMissing('assets', ['id' => $asset->id]);
    }

    public function test_reducing_balance_method_uses_diminishing_value(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)->post(route('companies.assets.store', $company), [
            'name' => 'Machine',
            'acquisition_date' => '2025-01-01',
            'cost' => 100000,
            'residual_value' => 10000,
            'useful_life_years' => 5,
            'depreciation_method' => 'reducing_balance',
        ])->assertRedirect();

        $asset = $company->assets()->firstOrFail();
        $this->assertSame('reducing_balance', $asset->effectiveDepreciationMethod());

        // Annual rate = 1 - (10000/100000)^(1/5) = 1 - 0.1^0.2 ≈ 0.36904.
        // After 1 year NBV ≈ 100000 * 0.63096 = 63096; acc dep ≈ 36904.
        $accDep = $asset->accumulatedDepreciation('2026-01-01');
        $this->assertEqualsWithDelta(36904.0, $accDep, 50.0);

        // Reducing balance depreciates faster in year 1 than straight-line (18000).
        $this->assertGreaterThan(18000.0, $accDep);

        // Never falls below residual value.
        $this->assertGreaterThanOrEqual(10000.0, $asset->netBookValue('2040-01-01'));
    }

    public function test_invalid_depreciation_method_is_rejected(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)->post(route('companies.assets.store', $company), [
            'name' => 'Bad',
            'acquisition_date' => '2025-01-01',
            'cost' => 1000,
            'depreciation_method' => 'made_up',
        ])->assertSessionHasErrors('depreciation_method');
    }

    public function test_can_add_ppe_class_and_movement_schedule_is_built_from_assets(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)->post(route('companies.assets.classes.store', $company), [
            'name' => 'Vehicles',
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
        ])->assertRedirect(route('companies.assets.index', $company));

        $class = $company->ppeClasses()->firstOrFail();

        $this->actingAs($user)->post(route('companies.assets.store', $company), [
            'name' => 'Truck',
            'ppe_class_id' => $class->id,
            'acquisition_date' => '2025-01-01',
            'cost' => 100000,
            'residual_value' => 0,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
        ])->assertRedirect();

        $schedule = app(\App\Services\PpeMovementService::class)
            ->build($company, '2025-01-01', '2025-12-31');

        $this->assertCount(1, $schedule);
        $row = $schedule[0];
        $this->assertSame('Vehicles', $row['class']->name);
        $this->assertEqualsWithDelta(100000.0, $row['additions'], 0.5);
        $this->assertEqualsWithDelta(100000.0, $row['cost_closing'], 0.5);
        // ~One year straight-line on 100000 over 5 years ≈ 20000 (period ends 31 Dec,
        // i.e. just under a full 12 months, so allow a small tolerance).
        $this->assertEqualsWithDelta(20000.0, $row['depreciation_charge'], 200.0);
        $this->assertEqualsWithDelta(80000.0, $row['carrying_closing'], 200.0);
    }

    public function test_cannot_manage_another_users_assets(): void
    {
        $owner = User::factory()->create();
        $company = $this->company($owner);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('companies.assets.index', $company))
            ->assertForbidden();
    }
}
