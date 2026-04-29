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
        Schema::create('equipment_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_session_id')->constrained('loading_sessions')->cascadeOnDelete();
            
            // === KATROL ===
            $table->string('pulley_top_status'); // ok, not_ok
            $table->text('pulley_top_notes')->nullable();
            $table->string('pulley_top_photo')->nullable();
            
            $table->string('pulley_bottom_status');
            $table->text('pulley_bottom_notes')->nullable();
            $table->string('pulley_bottom_photo')->nullable();
            
            // === TALI MONO ===
            $table->string('mono_rope_condition'); // new, worn
            $table->text('mono_rope_notes')->nullable();
            $table->string('mono_rope_photo')->nullable();
            
            // === RANTAI ===
            $table->string('chain_strength'); // strong, loose
            $table->text('chain_notes')->nullable();
            $table->string('chain_photo')->nullable();
            
            // === MUR/Baut ===
            $table->string('bolt_nut_status'); // tight, loose
            $table->text('bolt_nut_notes')->nullable();
            $table->string('bolt_nut_photo')->nullable();
            
            // === BAMBU ===
            $table->string('bamboo_condition'); // thick, cracked
            $table->text('bamboo_notes')->nullable();
            $table->string('bamboo_photo')->nullable();
            
            // === TANGGA ===
            $table->string('ladder_stability'); // stable, unstable
            $table->text('ladder_notes')->nullable();
            $table->string('ladder_photo')->nullable();
            
            // === SPONDS ===
            $table->string('sponds_cleanliness'); // clean, dirty
            $table->text('sponds_notes')->nullable();
            $table->string('sponds_photo')->nullable();
            
            // Additional Equipment
            $table->text('other_equipment')->nullable();
            $table->text('other_equipment_notes')->nullable();
            
            // Safety Summary
            $table->boolean('pulley_safe')->default(false);
            $table->boolean('mono_rope_safe')->default(false);
            $table->boolean('chain_safe')->default(false);
            $table->boolean('bolt_nut_safe')->default(false);
            $table->boolean('bamboo_safe')->default(false);
            $table->boolean('ladder_safe')->default(false);
            $table->boolean('sponds_safe')->default(false);
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
        Schema::dropIfExists('equipment_checks');
    }
};
