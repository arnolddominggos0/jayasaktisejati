<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function fc_can_access_fc_but_not_admin(): void
    {
        /** @var \App\Models\User $fc */
        $fc = User::factory()->create();
        $fc->assignRole('field_coordinator');

        $this->actingAs($fc);

        $this->get('/fc')->assertOk();          
        $this->get('/admin')->assertStatus(403); 
    }

    /** @test */
    public function admin_can_access_admin_but_not_fc(): void
    {
        /** @var \App\Models\User $admin */
        $admin = User::factory()->create();
        $admin->assignRole('office_admin');

        $this->actingAs($admin);

        $this->get('/admin')->assertOk();       // harus bisa masuk dashboard Admin
        $this->get('/fc')->assertStatus(403);    // harus ditolak di FC
    }
}
