<?php

namespace Tests\Feature\FC;

use App\Models\Branch;
use App\Models\Depot;
use App\Models\Pool;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CanonicalScopeModelTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    /** @test */
    public function user_guard_rejects_inconsistent_depot_scope(): void
    {
        $branchA = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);
        $branchB = Branch::create(['code' => 'MDO', 'name' => 'Manado']);

        $fc = User::factory()->create(['branch_id' => $branchA->id]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branchA->id,
            'coordinator_user_id' => $fc->id,
        ]);

        // Mismatched branch should throw.
        $this->expectException(\InvalidArgumentException::class);
        $fc->forceFill([
            'scope_branch_id' => $branchB->id,
            'scope_unit_id' => $depot->id,
            'scope_unit_type' => 'depot',
        ])->save();
    }

    /** @test */
    public function user_guard_rejects_scope_unit_that_belongs_to_another_user(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);

        $fcA = User::factory()->create(['branch_id' => $branch->id]);
        $fcA->assignRole('field_coordinator');

        $fcB = User::factory()->create(['branch_id' => $branch->id]);
        $fcB->assignRole('field_coordinator');

        Depot::create([
            'code' => 'DP-JKT-A',
            'name' => 'Depo A',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fcA->id,
        ]);

        $depotB = Depot::create([
            'code' => 'DP-JKT-B',
            'name' => 'Depo B',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fcB->id,
        ]);

        // fcA trying to claim fcB's depot should throw.
        $this->expectException(\InvalidArgumentException::class);
        $fcA->forceFill([
            'scope_branch_id' => $branch->id,
            'scope_unit_id' => $depotB->id,
            'scope_unit_type' => 'depot',
        ])->save();
    }

    /** @test */
    public function depot_guard_rejects_double_assignment_across_tables(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);

        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        Pool::create([
            'code' => 'PL-JKT',
            'name' => 'Pool Jakarta',
            'mode' => 'land',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);
    }

    /** @test */
    public function pool_guard_rejects_double_assignment_within_pools(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);

        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        Pool::create([
            'code' => 'PL-JKT-1',
            'name' => 'Pool 1',
            'mode' => 'land',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $this->expectException(\InvalidArgumentException::class);
        Pool::create([
            'code' => 'PL-JKT-2',
            'name' => 'Pool 2',
            'mode' => 'land',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);
    }

    /** @test */
    public function middleware_blocks_fc_when_canonical_scope_mismatches(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);

        $fc = User::factory()->create(['branch_id' => $branch->id]);
        $fc->assignRole('field_coordinator');

        // Inject mismatched canonical scope bypassing model guard
        $fc->forceFill([
            'scope_branch_id' => $branch->id,
            'scope_unit_id' => 9999, // non-existent / mismatched
            'scope_unit_type' => 'depot',
        ])->saveQuietly();

        Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $this->actingAs($fc);

        $response = $this->get(route('filament.fc.pages.dashboard'));
        $response->assertStatus(409);
    }

    /** @test */
    public function middleware_allows_fc_when_canonical_scope_matches(): void
    {
        $branch = Branch::create(['code' => 'JKT', 'name' => 'Jakarta']);

        $fc = User::factory()->create([
            'branch_id' => $branch->id,
            'scope_branch_id' => null,
            'scope_unit_id' => null,
            'scope_unit_type' => null,
        ]);
        $fc->assignRole('field_coordinator');

        $depot = Depot::create([
            'code' => 'DP-JKT',
            'name' => 'Depo Jakarta',
            'mode' => 'sea',
            'branch_id' => $branch->id,
            'coordinator_user_id' => $fc->id,
        ]);

        $this->actingAs($fc);

        // Should pass because scope_* are null (not yet backfilled), so guard is skipped.
        $response = $this->get(route('filament.fc.pages.dashboard'));
        $response->assertSuccessful();
    }
}
