<?php

namespace Tests\Feature;

use App\Events\AssetDisposed;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Company;
use App\Models\PpeClass;
use App\Models\User;
use App\Services\RoadRunnerAssetPostingDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Mockery;
use Tests\TestCase;

class AssetActionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Company $company;
    private Asset $asset;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->company = Company::create([
            'user_id' => $this->user->id,
            'registered_name' => 'Action Co',
            'company_type' => 'pty_ltd',
            'financial_year_end_month' => 12,
        ]);

        $class = PpeClass::create([
            'company_id' => $this->company->id,
            'name' => 'Vehicles',
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
        ]);

        $this->asset = Asset::withoutEvents(fn () => Asset::create([
            'company_id' => $this->company->id,
            'ppe_class_id' => $class->id,
            'name' => 'Test Vehicle',
            'acquisition_date' => '2025-01-01',
            'cost' => 200000,
            'residual_value' => 20000,
            'useful_life_years' => 5,
            'depreciation_method' => 'straight_line',
        ]));
    }

    private function mockDispatcher(string $expectedAction): Mockery\MockInterface
    {
        $mock = Mockery::mock(RoadRunnerAssetPostingDispatcher::class);
        $mock->shouldReceive('dispatch')
            ->once()
            ->withArgs(fn ($eventId, $assetId, $userId, $action) =>
                $action === $expectedAction && $assetId === $this->asset->id
            );
        $this->app->instance(RoadRunnerAssetPostingDispatcher::class, $mock);
        return $mock;
    }

    public function test_revalue_creates_event_and_dispatches(): void
    {
        $this->mockDispatcher('revalue');

        $this->actingAs($this->user)
            ->post(route('companies.assets.revalue', [$this->company, $this->asset]), [
                'new_carrying_amount' => 250000,
                'date' => '2025-06-15',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('asset_events', [
            'asset_id' => $this->asset->id,
            'event_type' => AssetEvent::TYPE_REVALUATION,
            'amount' => 250000,
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);
    }

    public function test_impair_creates_event_with_reason(): void
    {
        $this->mockDispatcher('impair');

        $this->actingAs($this->user)
            ->post(route('companies.assets.impair', [$this->company, $this->asset]), [
                'impairment_amount' => 30000,
                'date' => '2025-06-15',
                'reason' => 'Market downturn',
            ])
            ->assertRedirect();

        $event = AssetEvent::where('asset_id', $this->asset->id)
            ->where('event_type', AssetEvent::TYPE_IMPAIRMENT)
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals(30000, $event->amount);
        $this->assertStringContainsString('Market downturn', $event->description);
    }

    public function test_reverse_impairment_creates_event(): void
    {
        $this->mockDispatcher('reverse');

        $this->actingAs($this->user)
            ->post(route('companies.assets.reverse-impairment', [$this->company, $this->asset]), [
                'reversal_amount' => 15000,
                'date' => '2025-07-01',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('asset_events', [
            'asset_id' => $this->asset->id,
            'event_type' => AssetEvent::TYPE_IMPAIRMENT_REVERSAL,
            'amount' => 15000,
        ]);
    }

    public function test_capitalise_subsequent_cost(): void
    {
        $this->mockDispatcher('capitalise');

        $this->actingAs($this->user)
            ->post(route('companies.assets.capitalise', [$this->company, $this->asset]), [
                'amount' => 50000,
                'date' => '2025-08-01',
                'description' => 'Engine overhaul',
            ])
            ->assertRedirect();

        $event = AssetEvent::where('asset_id', $this->asset->id)
            ->where('event_type', AssetEvent::TYPE_CAPITALISATION)
            ->first();

        $this->assertNotNull($event);
        $this->assertEquals(50000, $event->amount);
        $this->assertStringContainsString('Engine overhaul', $event->description);
    }

    public function test_dispose_records_event_and_updates_asset(): void
    {
        $this->mockDispatcher('dispose');
        Event::fake([AssetDisposed::class]);

        $this->actingAs($this->user)
            ->post(route('companies.assets.dispose', [$this->company, $this->asset]), [
                'disposal_date' => '2025-09-01',
                'disposal_proceeds' => 80000,
            ])
            ->assertRedirect();

        $this->asset->refresh();
        $this->assertEquals('2025-09-01', $this->asset->disposal_date->format('Y-m-d'));
        $this->assertEquals(80000, $this->asset->disposal_proceeds);

        $this->assertDatabaseHas('asset_events', [
            'asset_id' => $this->asset->id,
            'event_type' => AssetEvent::TYPE_DISPOSAL,
            'amount' => 80000,
        ]);
    }

    public function test_revalue_requires_valid_data(): void
    {
        $this->app->instance(
            RoadRunnerAssetPostingDispatcher::class,
            Mockery::mock(RoadRunnerAssetPostingDispatcher::class)
                ->shouldNotReceive('dispatch')->getMock()
        );

        $this->actingAs($this->user)
            ->post(route('companies.assets.revalue', [$this->company, $this->asset]), [
                'new_carrying_amount' => -100,
                'date' => 'not-a-date',
            ])
            ->assertSessionHasErrors(['new_carrying_amount', 'date']);
    }

    public function test_other_user_cannot_perform_actions(): void
    {
        $this->app->instance(
            RoadRunnerAssetPostingDispatcher::class,
            Mockery::mock(RoadRunnerAssetPostingDispatcher::class)
                ->shouldNotReceive('dispatch')->getMock()
        );

        $intruder = User::factory()->create();

        $this->actingAs($intruder)
            ->post(route('companies.assets.revalue', [$this->company, $this->asset]), [
                'new_carrying_amount' => 300000,
                'date' => '2025-06-15',
            ])
            ->assertForbidden();
    }

    public function test_event_history_returns_all_events(): void
    {
        AssetEvent::create([
            'asset_id' => $this->asset->id,
            'event_type' => AssetEvent::TYPE_REVALUATION,
            'event_date' => '2025-06-01',
            'amount' => 250000,
            'description' => 'Revaluation',
            'journal_status' => AssetEvent::STATUS_POSTED,
        ]);

        AssetEvent::create([
            'asset_id' => $this->asset->id,
            'event_type' => AssetEvent::TYPE_IMPAIRMENT,
            'event_date' => '2025-07-01',
            'amount' => 30000,
            'description' => 'Impairment',
            'journal_status' => AssetEvent::STATUS_PENDING,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson(route('companies.assets.history', [$this->company, $this->asset]))
            ->assertOk()
            ->assertJsonCount(2, 'events');

        $events = $response->json('events');
        $this->assertEquals(AssetEvent::TYPE_REVALUATION, $events[0]['event_type']);
        $this->assertEquals(AssetEvent::TYPE_IMPAIRMENT, $events[1]['event_type']);
    }

    // --- API endpoint tests ---

    public function test_api_revalue(): void
    {
        $this->mockDispatcher('revalue');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/assets/{$this->asset->id}/revalue", [
                'new_carrying_amount' => 280000,
                'date' => '2025-06-20',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_api_impair(): void
    {
        $this->mockDispatcher('impair');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/assets/{$this->asset->id}/impair", [
                'impairment_amount' => 25000,
                'date' => '2025-06-20',
                'reason' => 'Flood damage',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_api_reverse_impairment(): void
    {
        $this->mockDispatcher('reverse');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/assets/{$this->asset->id}/reverse-impairment", [
                'reversal_amount' => 10000,
                'date' => '2025-07-01',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_api_capitalise(): void
    {
        $this->mockDispatcher('capitalise');

        $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/assets/{$this->asset->id}/capitalise", [
                'amount' => 40000,
                'date' => '2025-08-01',
                'description' => 'New tyres',
            ])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_api_unauthorised_user_gets_403(): void
    {
        $this->app->instance(
            RoadRunnerAssetPostingDispatcher::class,
            Mockery::mock(RoadRunnerAssetPostingDispatcher::class)
                ->shouldNotReceive('dispatch')->getMock()
        );

        $other = User::factory()->create();

        $this->actingAs($other, 'sanctum')
            ->postJson("/api/assets/{$this->asset->id}/revalue", [
                'new_carrying_amount' => 300000,
                'date' => '2025-06-20',
            ])
            ->assertForbidden();
    }
}
