<?php

namespace Tests\Feature\FC;

use App\Models\AppSheetSyncLog;
use App\Models\Branch;
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

class AppSheetCanonicalScopeTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    private function createFcWithCanonicalScope(string $code = 'JKT', string $name = 'Jakarta'): array
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

        // Populate canonical scope
        $fc->forceFill([
            'scope_branch_id' => $branch->id,
            'scope_unit_id' => $depot->id,
            'scope_unit_type' => 'depot',
        ])->saveQuietly();

        return [$branch, $fc, $depot];
    }

    /** @test */
    public function it_accepts_payload_when_canonical_scope_matches_exactly(): void
    {
        [$branch, $fc, $depot] = $this->createFcWithCanonicalScope();

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
        ]);
    }

    /** @test */
    public function it_rejects_payload_when_canonical_depot_mismatches(): void
    {
        [$branch, $fc, $depot] = $this->createFcWithCanonicalScope();

        $otherDepot = Depot::create([
            'code' => 'DP-OTHER',
            'name' => 'Depo Other',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => null,
        ]);

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $otherDepot->id,
                'Koordinator ID' => $fc->id,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false, 'error_code' => 'SCOPE_VIOLATION']);
<<<<<<< HEAD
        $this->assertStringStartsWith('[SCOPE_MISMATCH]', $response->json('message'));
=======
        $this->assertStringContainsString('[SCOPE_MISMATCH]', $response->json('message'));
>>>>>>> e433085b6f469c495465fdc0d5893050aef28eb9

        $this->assertDatabaseHas('appsheet_sync_logs', [
            'table_name' => 'briefing_sessions',
            'status' => 'failed',
        ]);
    }

    /** @test */
    public function it_rejects_payload_when_canonical_branch_mismatches(): void
    {
        [$jktBranch, $jktFc, $jktDepot] = $this->createFcWithCanonicalScope('JKT', 'Jakarta');
        [$mdoBranch, $mdoFc, $mdoDepot] = $this->createFcWithCanonicalScope('MDO', 'Manado');

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $jktFc->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $mdoDepot->id,
                'Koordinator ID' => $jktFc->id,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false, 'error_code' => 'SCOPE_VIOLATION']);
<<<<<<< HEAD
        $this->assertStringStartsWith('[SCOPE_MISMATCH]', $response->json('message'));
=======
        $this->assertStringContainsString('[SCOPE_MISMATCH]', $response->json('message'));
>>>>>>> e433085b6f469c495465fdc0d5893050aef28eb9
    }

    /** @test */
    public function it_rejects_impersonation_when_coordinator_id_differs_from_submitter(): void
    {
        [$branch, $fc, $depot] = $this->createFcWithCanonicalScope();

        $otherFc = User::factory()->create(['branch_id' => $branch->id]);
        $otherFc->assignRole('field_coordinator');

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $depot->id,
                'Koordinator ID' => $otherFc->id,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false, 'error_code' => 'SCOPE_VIOLATION']);
<<<<<<< HEAD
        $this->assertStringStartsWith('[IMPERSONATION_REJECTED]', $response->json('message'));
=======
        $this->assertStringContainsString('[IMPERSONATION_REJECTED]', $response->json('message'));
>>>>>>> e433085b6f469c495465fdc0d5893050aef28eb9
    }

    /** @test */
    public function it_rejects_canonical_pool_user_targeting_depot_payload(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        // Assign FC to a POOL (not depot)
        $pool = \App\Models\Pool::create([
            'code' => 'PL-JKT',
            'name' => 'Pool Jakarta',
            'mode' => 'land',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $fc->forceFill([
            'scope_branch_id' => $branch->id,
            'scope_unit_id' => $pool->id,
            'scope_unit_type' => 'pool',
        ])->saveQuietly();

        // Payload targets a depot — FC's canonical unit is a pool
        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => null,
        ]);

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $depot->id,
                'Koordinator ID' => $fc->id,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertStatus(403)
            ->assertJsonFragment(['success' => false, 'error_code' => 'SCOPE_VIOLATION']);
<<<<<<< HEAD
        $this->assertStringStartsWith('[SCOPE_MISMATCH]', $response->json('message'));
=======
        $this->assertStringContainsString('[SCOPE_MISMATCH]', $response->json('message'));
>>>>>>> e433085b6f469c495465fdc0d5893050aef28eb9
    }

    /** @test */
    public function legacy_user_without_canonical_scope_still_works(): void
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

        // NO canonical scope set (legacy)

        $payload = [
            'table' => 'briefing_sessions',
            'operation' => 'create',
            'submitted_by_user_id' => $fc->id,
            'data' => [
                'Tanggal' => now()->toDateString(),
                'Depot ID' => $depot->id,
                'Koordinator ID' => $fc->id,
                'Catatan' => 'Briefing pagi',
            ],
        ];

        $response = $this->postJson('/api/appsheet/webhook', $payload);

        $response->assertOk()
            ->assertJsonFragment(['success' => true]);
    }

    /** @test */
    public function legacy_user_without_canonical_scope_rejected_on_wrong_branch(): void
    {
        [$jktBranch, $jktFc, $jktDepot] = $this->createLegacyFc('JKT', 'Jakarta');
        [$mdoBranch, $mdoFc, $mdoDepot] = $this->createLegacyFc('MDO', 'Manado');

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
            ->assertJsonFragment(['success' => false, 'error_code' => 'SCOPE_VIOLATION']);
<<<<<<< HEAD
        $this->assertStringStartsWith('[LEGACY_SCOPE_DENIED]', $response->json('message'));
=======
        $this->assertStringContainsString('[LEGACY_SCOPE_DENIED]', $response->json('message'));
>>>>>>> e433085b6f469c495465fdc0d5893050aef28eb9
    }

    private function createLegacyFc(string $code = 'JKT', string $name = 'Jakarta'): array
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
}
