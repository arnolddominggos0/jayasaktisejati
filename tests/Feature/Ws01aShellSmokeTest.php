<?php

namespace Tests\Feature;

use App\Filament\Resources\ShipmentResource;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * WS-01A/A.1 temporary smoke test — verifies the workspace shell renders.
 * Delete after the sprint verification (not part of the permanent suite).
 */
class Ws01aShellSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    /** @test */
    public function shipment_workspace_shell_renders(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super_admin');

        $this->actingAs($admin, 'web');

        $response = $this->get(ShipmentResource::getUrl('index'));

        $response->assertOk();

        // Header per WS-01A
        $response->assertSee('Permintaan Pengiriman');
        $response->assertSee('Kelola seluruh permintaan pengiriman sebelum diproses operasional.');
        $response->assertSee('Buat Permintaan');

        // Tab navigation shell
        $response->assertSee('Menunggu Penjemputan');
        $response->assertSee('Perlu Tindakan');

        // Toolbar: export moved into overflow, filter toggle present
        $response->assertSee('Export CSV');
        $response->assertSee('ws-filter-toggle', false);
        $response->assertSee('ws-shipment-workspace', false);
    }
}
