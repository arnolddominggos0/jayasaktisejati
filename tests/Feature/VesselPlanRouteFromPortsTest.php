<?php

namespace Tests\Feature;

use App\Filament\Resources\VesselPlanResource\Pages\CreateVesselPlan;
use App\Models\Port;
use App\Models\User;
use App\Models\VesselPlan;
use App\Supports\BusinessRouteResolver;
use App\Supports\RouteCode;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * DATA-04 — Route berasal dari POL + POD.
 *
 * route_code tidak lagi dipilih user, tetapi diturunkan dari pasangan port.
 * Ini business-critical karena route_code ikut membentuk identitas voyage
 * (voyages.code = VOY{voyage_no}{vessel.code}{route_code}).
 */
class VesselPlanRouteFromPortsTest extends TestCase
{
    public function test_registry_has_no_duplicate_unlocode_pairs(): void
    {
        $pairs = collect(RouteCode::all())
            ->map(fn (array $r) => $r[5] . '|' . $r[6]);

        $this->assertSame(
            $pairs->count(),
            $pairs->unique()->count(),
            'Satu pasangan UNLOCODE hanya boleh dimiliki satu business route, '
            .'kalau tidak POL/POD tidak bisa menentukan route secara unik.'
        );
    }

    public function test_route_code_is_derived_from_selected_ports(): void
    {
        $admin = User::query()->where('email', 'admin@jss.local')->first();

        if (! $admin) {
            $this->markTestSkipped('super admin seed tidak tersedia.');
        }

        $this->actingAs($admin);

        $pol = Port::query()->where('code', 'IDJKT')->firstOrFail();
        $pod = Port::query()->where('code', 'IDBTG')->firstOrFail();

        DB::beginTransaction();

        try {
            Livewire::test(CreateVesselPlan::class)
                ->fillForm([
                    'period_month' => '2033-09-01',
                    'pol_id'       => $pol->getKey(),
                    'pod_id'       => $pod->getKey(),
                ])
                ->call('create')
                ->assertHasNoFormErrors();

            $plan = VesselPlan::query()
                ->whereDate('period_month', '2033-09-01')
                ->firstOrFail();

            // Diturunkan dari port, bukan dari input user
            $this->assertSame('JKT-MND', $plan->route_code);
            $this->assertSame($pol->getKey(), $plan->pol_id);
            $this->assertSame($pod->getKey(), $plan->pod_id);

            // Voyage suffix tetap konsisten dengan data yang sudah dimigrasikan
            $this->assertSame('JKTMND', RouteCode::voyage($plan->route_code));

            // Tampilan rute berasal dari POL/POD
            $this->assertSame('Jakarta → Bitung', BusinessRouteResolver::forPlan($plan));
        } finally {
            DB::rollBack();
        }
    }
}
