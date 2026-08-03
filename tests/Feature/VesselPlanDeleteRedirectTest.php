<?php

namespace Tests\Feature;

use App\Enums\VesselPlanStatus;
use App\Filament\Resources\VesselPlanResource;
use App\Filament\Resources\VesselPlanResource\Pages\EditVesselPlan;
use App\Models\User;
use App\Models\VesselPlan;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * DATA-03 — Bug: setelah delete, halaman Edit (beserta Relation Manager-nya)
 * masih ter-mount dan mencoba render terhadap record yang sudah terhapus.
 * Perbaikannya adalah redirect ke List setelah delete.
 */
class VesselPlanDeleteRedirectTest extends TestCase
{
    public function test_deleting_a_draft_redirects_to_list_and_does_not_rerender(): void
    {
        $admin = User::query()->where('email', 'admin@jss.local')->first();

        if (! $admin) {
            $this->markTestSkipped('super admin seed tidak tersedia di database test.');
        }

        $this->actingAs($admin);

        $ports = (new VesselPlan(['route_code' => 'JKT-BTG']))->resolveRoutePortIds();

        $plan = VesselPlan::create([
            'period_month' => '2031-03-01',
            'route_code'   => 'JKT-BTG',
            'pol_id'       => $ports['pol_id'],
            'pod_id'       => $ports['pod_id'],
            'status'       => VesselPlanStatus::Draft,
        ]);

        Livewire::test(EditVesselPlan::class, ['record' => $plan->getKey()])
            ->callAction('hapus')
            ->assertHasNoErrors()
            ->assertRedirect(VesselPlanResource::getUrl('index'));

        $this->assertDatabaseMissing('vessel_plans', ['id' => $plan->getKey()]);
    }
}
