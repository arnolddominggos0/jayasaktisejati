<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rack_container_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_session_id')->constrained('loading_sessions')->cascadeOnDelete();
            
            // === PILAR RACK CONTAINER ===
            // Pilar Depan Kanan (A)
            $table->string('pillar_a_condition'); // strong_and_straight, not_straight, damaged
            $table->string('pillar_a_pulley_hook'); // present_and_strong, not_present, loose, damaged
            $table->string('pillar_a_tie_status'); // tied_strong, not_tied, loose
            $table->string('pillar_a_photo')->nullable();
            $table->text('pillar_a_notes')->nullable();
            
            // Pilar Depan Kiri (B)
            $table->string('pillar_b_condition');
            $table->string('pillar_b_pulley_hook');
            $table->string('pillar_b_tie_status');
            $table->string('pillar_b_photo')->nullable();
            $table->text('pillar_b_notes')->nullable();
            
            // Pilar Belakang Kanan (C)
            $table->string('pillar_c_condition');
            $table->string('pillar_c_pulley_hook');
            $table->string('pillar_c_tie_status');
            $table->string('pillar_c_photo')->nullable();
            $table->text('pillar_c_notes')->nullable();
            
            // Pilar Belakang Kiri (D)
            $table->string('pillar_d_condition');
            $table->string('pillar_d_pulley_hook');
            $table->string('pillar_d_tie_status');
            $table->string('pillar_d_photo')->nullable();
            $table->text('pillar_d_notes')->nullable();
            
            // === DROP FLOOR ===
            // Drop Floor Depan
            $table->string('drop_floor_front_condition'); // straight, bent
            $table->string('drop_floor_front_strength'); // strong, weak
            $table->string('drop_floor_front_iron_hook'); // present, not_present, damaged
            $table->string('drop_floor_front_photo')->nullable();
            $table->text('drop_floor_front_notes')->nullable();
            
            // Drop Floor Belakang
            $table->string('drop_floor_rear_condition');
            $table->string('drop_floor_rear_strength');
            $table->string('drop_floor_rear_iron_hook');
            $table->string('drop_floor_rear_photo')->nullable();
            $table->text('drop_floor_rear_notes')->nullable();
            
            // === STRUKTUR DALAM CONTAINER ===
            $table->string('container_wall_status'); // good, damaged, leaking
            $table->string('container_floor_status'); // good, damaged, leaking
            $table->string('container_roof_status'); // good, damaged, leaking
            $table->string('container_structure_photo')->nullable();
            $table->text('container_structure_notes')->nullable();
            
            // Safety Summary
            $table->boolean('all_pillars_safe')->default(false);
            $table->boolean('all_drop_floors_safe')->default(false);
            $table->boolean('container_structure_safe')->default(false);
            $table->boolean('overall_safe')->default(false);
            
            // Critical Issues Count
            $table->integer('critical_issues_count')->default(0);
            $table->integer('warning_issues_count')->default(0);
            
            // Checked by
            $table->foreignId('checked_by')->constrained('users');
            $table->timestamp('checked_at');
            
            $table->timestamps();
            
            // Indexes
            $table->index('loading_session_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rack_container_checks');
    }
};
