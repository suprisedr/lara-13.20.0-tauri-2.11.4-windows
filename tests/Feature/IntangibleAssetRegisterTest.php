<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IntangibleAssetRegisterTest extends TestCase
{
    use RefreshDatabase;

    private function company(User $user): Company
    {
        return Company::create([
            'user_id' => $user->id,
            'registered_name' => 'Intangible Co',
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12,
        ]);
    }

    public function test_register_renders(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)
            ->get(route('companies.intangibles.index', $company))
            ->assertOk()
            ->assertSee('Intangible Assets Register');
    }

    public function test_can_add_edit_and_remove_with_amortisation(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)->post(route('companies.intangibles.store', $company), [
            'name' => 'Software licence',
            'acquisition_date' => '2025-01-01',
            'cost' => 30000,
            'residual_value' => 0,
            'useful_life_years' => 3,
            'amortisation_method' => 'straight_line',
        ])->assertRedirect(route('companies.intangibles.index', $company));

        $asset = $company->intangibleAssets()->firstOrFail();

        // 30000 / 3 = 10000 per year straight-line.
        $this->assertEqualsWithDelta(10000.0, $asset->accumulatedAmortisation('2026-01-01'), 0.5);
        $this->assertEqualsWithDelta(20000.0, $asset->netBookValue('2026-01-01'), 0.5);

        $this->actingAs($user)->patch(route('companies.intangibles.update', [$company, $asset]), [
            'name' => 'Software licence v2',
            'acquisition_date' => '2025-01-01',
            'cost' => 30000,
            'amortisation_method' => 'straight_line',
        ])->assertRedirect();
        $this->assertSame('Software licence v2', $asset->fresh()->name);

        $this->actingAs($user)->delete(route('companies.intangibles.destroy', [$company, $asset]))
            ->assertRedirect();
        $this->assertDatabaseMissing('intangible_assets', ['id' => $asset->id]);
    }

    public function test_invalid_amortisation_method_rejected(): void
    {
        $user = User::factory()->create();
        $company = $this->company($user);

        $this->actingAs($user)->post(route('companies.intangibles.store', $company), [
            'name' => 'X',
            'acquisition_date' => '2025-01-01',
            'cost' => 100,
            'amortisation_method' => 'nope',
        ])->assertSessionHasErrors('amortisation_method');
    }

    public function test_cannot_access_another_users_register(): void
    {
        $owner = User::factory()->create();
        $company = $this->company($owner);
        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->get(route('companies.intangibles.index', $company))
            ->assertForbidden();
    }
}
