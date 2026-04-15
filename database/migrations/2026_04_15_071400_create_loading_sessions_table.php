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
        Schema::create('loading_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); 
            $table->foreignId('briefing_session_id')->nullable()->constrained('briefing_sessions')->nullOnDelete();
            $table->foreignId('shipment_id')->nullable()->constrained('shipments')->nullOnDelete();
            $table->foreignId('depot_id')->constrained('depots');
            $table->foreignId('coordinator_user_id')->constrained('users');
            $table->foreignId('branch_id')->constrained('branches');
            
            // Operation Type
            $table->string('operation_type'); // loading, unloading, rack_handling
            
            // Session Status - Sequential Flow
            $table->string('status')->default('draft'); // LoadingStatus enum
            $table->string('current_step')->default('mp_attendance_check');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('stopped_at')->nullable();
            
            // Step completion tracking
            $table->boolean('mp_attendance_completed')->default(false);
            $table->boolean('health_check_completed')->default(false);
            $table->boolean('apd_check_completed')->default(false);
            $table->boolean('equipment_check_completed')->default(false);
            $table->boolean('rack_container_check_completed')->default(false);
            $table->boolean('unit_check_completed')->default(false);
            $table->boolean('stock_apd_check_completed')->default(false);
            $table->boolean('manpower_availability_completed')->default(false);
            $table->boolean('final_decision_completed')->default(false);
            
            // MP Summary
            $table->integer('mp_required')->default(0);
            $table->integer('mp_present')->default(0);
            $table->integer('mp_absent')->default(0);
            $table->integer('mp_sick')->default(0);
            $table->boolean('mp_sufficient')->default(false);
            
            // Health Summary
            $table->integer('mp_fit_count')->default(0);
            $table->integer('mp_unfit_count')->default(0);
            
            // APD Summary (Final Check)
            $table->boolean('apd_complete')->default(false);
            $table->boolean('apd_clean')->default(false);
            
            // Equipment Summary
            $table->boolean('equipment_safe')->default(false);
            
            // Rack Container Summary
            $table->boolean('rack_container_safe')->default(false);
            $table->boolean('rack_pillars_ok')->default(false);
            $table->boolean('drop_floor_ok')->default(false);
            $table->boolean('container_structure_ok')->default(false);
            
            // Unit Summary
            $table->boolean('unit_measurements_ok')->default(false);
            
            // Stock APD Summary
            $table->boolean('stock_apd_sufficient')->default(false);
            
            // Final Decision
            $table->string('final_decision_status')->nullable(); // go, warning, stop
            $table->text('final_decision_notes')->nullable();
            $table->foreignId('final_decision_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('final_decision_at')->nullable();
            
            // Location tracking
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->string('location_address')->nullable();
            
            // Critical Issues Count
            $table->integer('critical_issues_count')->default(0);
            $table->integer('warning_issues_count')->default(0);
            
            // Notes
            $table->text('general_notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('code');
            $table->index('status');
            $table->index('operation_type');
            $table->index('depot_id');
            $table->index('branch_id');
            $table->index('briefing_session_id');
            $table->index(['depot_id', 'status']);
            $table->index(['branch_id', 'status']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_sessions');
    }
};
