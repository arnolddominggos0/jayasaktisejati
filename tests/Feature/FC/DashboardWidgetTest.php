<?php

namespace Tests\Feature\FC;

use App\Enums\ShipmentStatus;
use App\Enums\TrackStatus;
use App\Models\Branch;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\Shipment;
use App\Models\ShipmentTrack;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class DashboardWidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createFcContext(): array
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');
        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        return [$fc, $branch, $depot];
    }

    private function createSeaShipment(Branch $branch, User $fc, Depot $depot, array $overrides = []): Shipment
    {
        $origin = City::factory()->create();
        $dest = City::factory()->create();
        $customer = Customer::factory()->create();

        return Shipment::create(array_merge([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $origin->id,
            'destination_city_id' => $dest->id,
            'branch_id' => $branch->id,
            'mode' => 'sea',
            'status' => 'pending',
            'service_type' => 'sea_freight',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => $depot->id,
            'coordinator_id' => $fc->id,
        ], $overrides));
    }

    private function getKpiBaseQuery(User $fc, ?int $branchId, ?int $depotId)
    {
        return Shipment::query()
            ->where('mode', 'sea')
            ->when($branchId, fn ($query) => $query->where(function ($w) use ($branchId) {
                $w->where('branch_id', $branchId)->orWhereNull('branch_id');
            }))
            ->when($depotId, fn ($query) => $query->where(function ($w) use ($depotId, $fc) {
                $w->where('assigned_depot_id', $depotId)
                    ->orWhere('coordinator_id', $fc->id);
            }), fn ($query) => $query->where('coordinator_id', $fc->id));
    }

    /** @test */
    public function fc_kpi_stats_counts_only_sea_shipments(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $this->createSeaShipment($branch, $fc, $depot, ['status' => ShipmentStatus::Transit->value]);
        $this->createSeaShipment($branch, $fc, $depot, ['mode' => 'land', 'status' => ShipmentStatus::Transit->value]);

        $base = $this->getKpiBaseQuery($fc, $branch->id, $depot->id);
        $this->assertEquals(1, $base->count());
    }

    /** @test */
    public function fc_kpi_stats_shows_urgent_count(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Transit->value,
            'priority' => 'urgent',
        ]);
        $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Transit->value,
            'priority' => 'normal',
        ]);

        $base = $this->getKpiBaseQuery($fc, $branch->id, $depot->id);
        $urgent = (clone $base)
            ->where('priority', 'urgent')
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
            ->count();

        $this->assertEquals(1, $urgent);
    }

    /** @test */
    public function fc_kpi_stats_excludes_delivered_and_cancelled_from_urgent(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Delivered->value,
            'priority' => 'urgent',
        ]);
        $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Cancelled->value,
            'priority' => 'urgent',
        ]);

        $base = $this->getKpiBaseQuery($fc, $branch->id, $depot->id);
        $urgent = (clone $base)
            ->where('priority', 'urgent')
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
            ->count();

        $this->assertEquals(0, $urgent);
    }

    /** @test */
    public function fc_attention_list_includes_urgent_hold_and_near_eta(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $urgent = $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Transit->value,
            'priority' => 'urgent',
        ]);
        $onHold = $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Hold->value,
        ]);
        $nearEta = $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Transit->value,
            'eta' => now()->addHours(6),
        ]);
        $normal = $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Transit->value,
            'priority' => 'normal',
            'eta' => now()->addDays(5),
        ]);

        $results = Shipment::query()
            ->where('mode', 'sea')
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
            ->where(function ($w) use ($depot, $fc) {
                $w->where('assigned_depot_id', $depot->id)
                    ->orWhere('coordinator_id', $fc->id);
            })
            ->where(function ($q) {
                $q->where('priority', 'urgent')
                    ->orWhere('status', ShipmentStatus::Hold->value)
                    ->orWhere(function ($q2) {
                        $q2->whereNotNull('eta')
                            ->where('eta', '<=', now()->addDay());
                    });
            })
            ->pluck('id')
            ->toArray();

        $this->assertContains($urgent->id, $results);
        $this->assertContains($onHold->id, $results);
        $this->assertContains($nearEta->id, $results);
        $this->assertNotContains($normal->id, $results);
    }

    /** @test */
    public function fc_recent_activities_only_shows_sea_tracks(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $seaShipment = $this->createSeaShipment($branch, $fc, $depot);
        $landShipment = $this->createSeaShipment($branch, $fc, $depot, ['mode' => 'land']);

        ShipmentTrack::create([
            'shipment_id' => $seaShipment->id,
            'status' => TrackStatus::Pickup->value,
            'status_normalized' => 0,
            'tracked_at' => now(),
        ]);
        ShipmentTrack::create([
            'shipment_id' => $landShipment->id,
            'status' => TrackStatus::Pickup->value,
            'status_normalized' => 0,
            'tracked_at' => now(),
        ]);

        $results = ShipmentTrack::query()
            ->whereNotNull('tracked_at')
            ->whereHas('shipment', function ($s) use ($depot, $fc) {
                $s->where('mode', 'sea')
                    ->where(function ($w) use ($depot, $fc) {
                        $w->where('assigned_depot_id', $depot->id)
                            ->orWhere('coordinator_id', $fc->id);
                    });
            })
            ->pluck('shipment_id')
            ->toArray();

        $this->assertContains($seaShipment->id, $results);
        $this->assertNotContains($landShipment->id, $results);
    }

    /** @test */
    public function fc_status_chart_query_returns_sea_tracks_grouped_by_day(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $shipment = $this->createSeaShipment($branch, $fc, $depot);

        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::Pickup->value,
            'status_normalized' => 0,
            'tracked_at' => now(),
        ]);
        ShipmentTrack::create([
            'shipment_id' => $shipment->id,
            'status' => TrackStatus::Handover->value,
            'status_normalized' => 0,
            'tracked_at' => now()->subDay(),
        ]);

        $rows = ShipmentTrack::query()
            ->selectRaw("date(tracked_at) as d, status, count(*) as c")
            ->whereNotNull('tracked_at')
            ->whereBetween('tracked_at', [now()->subDays(13)->startOfDay(), now()->endOfDay()])
            ->whereHas('shipment', function ($s) use ($depot, $fc) {
                $s->where('mode', 'sea')
                    ->where(function ($w) use ($depot, $fc) {
                        $w->where('assigned_depot_id', $depot->id)
                            ->orWhere('coordinator_id', $fc->id);
                    });
            })
            ->groupBy('d', 'status')
            ->get();

        $this->assertCount(2, $rows);
    }

    /** @test */
    public function fc_dashboard_displays_branch_context_header(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $this->actingAs($fc);

        $response = $this->get('/fc/dashboard');

        $response->assertOk();
        $response->assertSee($branch->name);
        $response->assertSee($depot->name);
        $response->assertSee('Lingkup Operasional');
        $response->assertSee('Koordinator Lapangan');
        $response->assertSee('Mode: Laut');
    }

    /** @test */
    public function fc_dashboard_page_has_branch_context_methods(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $this->actingAs($fc);

        // Test the Dashboard page methods directly
        $dashboard = new \App\Filament\FC\Pages\Dashboard\Dashboard();

        $this->assertEquals($branch->name, $dashboard->getBranchName());
        $this->assertEquals($depot->name, $dashboard->getDepotName());
        $this->assertTrue($dashboard->hasBranchContext());
        $this->assertTrue($dashboard->hasDepotContext());
    }

    /** @test */
    public function fc_attention_list_returns_branch_aware_empty_state_text(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $this->actingAs($fc);

        // Test the widget directly since Livewire widgets aren't rendered in initial HTML
        $widget = new \App\Filament\FC\Widgets\FcAttentionList();
        $reflection = new \ReflectionClass($widget);
        $method = $reflection->getMethod('getBranchName');
        $method->setAccessible(true);

        $branchName = $method->invoke($widget);
        $this->assertEquals($branch->name, $branchName);
    }

    /** @test */
    public function fc_kpi_eta_dekat_shows_warning_when_count_positive(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        // Create a shipment with near ETA (within 24 hours)
        $this->createSeaShipment($branch, $fc, $depot, [
            'status' => ShipmentStatus::Transit->value,
            'eta' => now()->addHours(6),
        ]);

        $base = $this->getKpiBaseQuery($fc, $branch->id, $depot->id);
        $nearEta = (clone $base)
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
            ->where(function ($q) {
                $q->whereNull('eta')
                    ->orWhere('eta', '<=', now()->addDay());
            })
            ->count();

        $this->assertEquals(1, $nearEta);
    }

    /** @test */
    public function fc_dashboard_page_has_all_widgets_configured(): void
    {
        [$fc, $branch, $depot] = $this->createFcContext();

        $this->actingAs($fc);

        $response = $this->get('/fc/dashboard');

        $response->assertOk();
        // Verify widgets are referenced in the view (by class name in HTML comment or wire:snapshot)
        $response->assertSee('fc-kpi-stats');
        $response->assertSee('fc-attention-list');
        $response->assertSee('fc-status-chart');
        $response->assertSee('fc-recent-activities');
    }
}
