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
        Schema::create('loading_final_decisions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_session_id')->constrained('loading_sessions')->cascadeOnDelete();
            
            // Decision Status
            $table->string('status'); // go, warning, stop, pending, approved, rejected
            $table->string('category'); // automatic, manual
            
            // Decision Reason
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();
            
            // Critical Issues Summary
            $table->json('critical_issues')->nullable()->comment('List of critical issues found');
            $table->json('warning_issues')->nullable()->comment('List of warning issues found');
            
            // Conditions that triggered decision
            $table->boolean('pillar_issues')->default(false);
            $table->boolean('drop_floor_issues')->default(false);
            $table->boolean('pulley_issues')->default(false);
            $table->boolean('apd_incomplete')->default(false);
            $table->boolean('mp_unhealthy')->default(false);
            $table->boolean('equipment_unsafe')->default(false);
            $table->boolean('unit_unsafe')->default(false);
            $table->boolean('stock_apd_insufficient')->default(false);
            $table->boolean('mp_insufficient')->default(false);
            
            // Approval workflow for warning/pending status
            $table->foreignId('requested_by')->constrained('users');
            $table->timestamp('requested_at');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            
            // Corrective actions (if any)
            $table->text('corrective_action')->nullable();
            $table->timestamp('corrective_action_completed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('loading_session_id');
            $table->index('status');
            $table->index('requested_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_final_decisions');
    }
};
