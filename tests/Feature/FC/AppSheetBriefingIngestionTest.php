<?php

namespace Tests\Feature\FC;

use App\Models\AppSheetSyncLog;
use App\Models\Branch;
use App\Models\BriefingAttendance;
use App\Models\BriefingSession;
use App\Models\City;
use App\Models\Customer;
use App\Models\Depot;
use App\Models\LoadingSession;
use App\Models\Manpower;
use App\Models\Shipment;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class AppSheetBriefingIngestionTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createBranchAndDepot(string $code = 'JKT', string $name = 'Jakarta'): array
    {
        $branch = Branch::create(['code' => $code, 'name' => $name]);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => "DP-{$code}",
            'name' => "Depo {$name}",
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        return [$branch, $fc, $depot];
    }

    /** @test */
    public function it_ingests_briefing_session_with_valid_fc_scope(): void
    {
        [$branch, $fc, $depot] = $this->createBranchAndDepot();

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $depot->id,
                'Koordinator ID' => $fc->id,
                'Jumlah MP Dibutuhkan' => 5,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('briefing_sessions', [
            'date' => now()->toDateString(),
            'depot_id' => $depot->id,
            'summary_headcount' => 5,
        ]);

        $this->assertDatabaseHas('appsheet_sync_logs', [
            'table_name' => 'briefing_sessions',
            'status' => 'success',
            'synced_by' => $fc->name,
        ]);
    }

    /** @test */
    public function it_ingests_briefing_attendance_and_recalculates_session(): void
    {
        [$branch, $fc, $depot] = $this->createBranchAndDepot();

        $session = BriefingSession::create([
            'date' => now()->toDateString(),
            'depot_id' => $depot->id,
            'coordinator_user_id' => $fc->id,
            'summary_headcount' => 1,
        ]);

        $manpower = Manpower::create([
            'name' => 'John Doe',
            'branch_id' => $branch->id,
            'depot_id' => $depot->id,
            'active' => true,
        ]);

        $payload = [
            'table' => 'briefing_attendances',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Sesi ID' => $session->id,
                'MP ID' => $manpower->id,
                'Status Kehadiran' => 'present',
                'Suhu' => 36.5,
                'TD Sistolik' => 120,
                'TD Diastolik' => 80,
                'APD Lengkap' => true,
                'Catatan' => 'Sehat',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('briefing_attendances', [
            'session_id' => $session->id,
            'manpower_id' => $manpower->id,
            'attendance_status' => 'present',
        ]);

        $session->refresh();
        $this->assertTrue($session->summary_sufficient);

        $this->assertDatabaseHas('appsheet_sync_logs', [
            'table_name' => 'briefing_attendances',
            'status' => 'success',
        ]);
    }

    /** @test */
    public function it_rejects_briefing_sync_when_fc_has_wrong_branch_scope(): void
    {
        [$jktBranch, $jktFc, $jktDepot] = $this->createBranchAndDepot('JKT', 'Jakarta');

        $mdoBranch = Branch::create(['code' => 'MDO', 'name' => 'Manado']);
        $mdoFc = User::factory()->create(['branch_id' => $mdoBranch->id]);
        $mdoFc->assignRole('field_coordinator');

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $mdoFc->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $jktDepot->id,
                'Koordinator ID' => $mdoFc->id,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false]);

        $this->assertDatabaseHas('appsheet_sync_logs', [
            'table_name' => 'briefing_sessions',
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_rejects_when_user_is_not_field_coordinator(): void
    {
        [$branch, $fc, $depot] = $this->createBranchAndDepot();

        $officeAdmin = User::factory()->create(['branch_id' => $branch->id]);
        $officeAdmin->assignRole('office_admin');

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $officeAdmin->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $depot->id,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false]);
    }

    /** @test */
    public function it_allows_loading_session_sync_when_shipment_is_in_progress(): void
    {
        [$branch, $fc, $depot] = $this->createBranchAndDepot();

        $customer = Customer::factory()->create();
        [$originCityId, $destCityId] = [City::factory()->create()->id, City::factory()->create()->id];

        $shipment = Shipment::create([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $originCityId,
            'destination_city_id' => $destCityId,
            'branch_id' => $branch->id,
            'mode' => 'sea',
            'status' => 'draft',
            'service_type' => 'sea_freight',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => $depot->id,
        ]);
        $shipment->updateQuietly(['status' => 'pending']);

        $payload = [
            'table' => 'loading_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Code' => 'LD-TEST-001',
                'Jenis Operasi' => 'loading',
                'Status' => 'draft',
                'Depot ID' => $depot->id,
                'Koordinator ID' => $fc->id,
                'Branch ID' => $branch->id,
                'Shipment ID' => $shipment->id,
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);

        $this->assertDatabaseHas('loading_sessions', [
            'code' => 'LD-TEST-001',
            'shipment_id' => $shipment->id,
        ]);
    }

    /** @test */
    public function it_rejects_loading_session_sync_when_shipment_is_not_in_progress(): void
    {
        [$branch, $fc, $depot] = $this->createBranchAndDepot();

        $customer = Customer::factory()->create();
        [$originCityId, $destCityId] = [City::factory()->create()->id, City::factory()->create()->id];

        $shipment = Shipment::create([
            'code' => null,
            'customer_id' => $customer->id,
            'receiver_id' => $customer->id,
            'origin_city_id' => $originCityId,
            'destination_city_id' => $destCityId,
            'branch_id' => $branch->id,
            'mode' => 'sea',
            'status' => 'draft',
            'service_type' => 'sea_freight',
            'request_type' => 'wa_telp',
            'priority' => 'normal',
            'assigned_depot_id' => $depot->id,
        ]);
        $shipment->updateQuietly(['status' => 'delivered']);

        $payload = [
            'table' => 'loading_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Code' => 'LD-TEST-002',
                'Jenis Operasi' => 'loading',
                'Status' => 'draft',
                'Depot ID' => $depot->id,
                'Koordinator ID' => $fc->id,
                'Branch ID' => $branch->id,
                'Shipment ID' => $shipment->id,
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false]);

        $this->assertDatabaseHas('appsheet_sync_logs', [
            'table_name' => 'loading_sessions',
            'status' => 'failed',
        ]);
    }
}
