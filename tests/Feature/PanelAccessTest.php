<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create required roles
        foreach (['super_admin', 'office_admin', 'field_coordinator'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
    }

    /** @test */
    public function fc_can_access_fc_but_not_admin(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $fc = User::factory()->create();
        $fc->assignRole('field_coordinator');

        $this->actingAs($fc, 'web');

        $this->get('/fc')->assertStatus(403);

        $this->get('/admin')->assertStatus(403);
    }

    /** @test */
    public function admin_can_access_admin_but_not_fc(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('office_admin');

        $this->actingAs($admin, 'web');

        $this->followingRedirects();

        $this->get('/admin')->assertOk();
        $this->get('/fc')->assertStatus(403);
    }
}
