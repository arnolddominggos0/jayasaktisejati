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
        Schema::create('unit_checks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_session_id')->constrained('loading_sessions')->cascadeOnDelete();
            $table->foreignId('armada_id')->nullable()->constrained('armadas')->nullOnDelete();
            
            // Unit/Vehicle Info
            $table->string('unit_type')->nullable(); // mobil, truk, dll
            $table->string('unit_plate_number')->nullable();
            
            // === PARAMETER UKURAN WAJIB (dalam cm) ===
            $table->integer('distance_front_rh')->nullable()->comment('Jarak Front RH (cm)');
            $table->integer('distance_rear_rh')->nullable()->comment('Jarak Rear RH (cm)');
            $table->integer('distance_back_door')->nullable()->comment('Jarak Back Door (cm)');
            $table->integer('distance_rear_lh')->nullable()->comment('Jarak Rear LH (cm)');
            $table->integer('distance_front_lh')->nullable()->comment('Jarak Front LH (cm)');
            $table->integer('drop_floor_front_height')->nullable()->comment('Tinggi Drop Floor Depan (cm)');
            $table->integer('drop_floor_rear_height')->nullable()->comment('Tinggi Drop Floor Belakang (cm)');
            $table->integer('container_roof_distance')->nullable()->comment('Jarak Atap Container (cm)');
            
            // Validation ranges (optional, for reference)
            $table->json('validation_ranges')->nullable()->comment('Min/max values for validation');
            
            // Photos - Multiple angles
            $table->string('photo_front_view')->nullable()->comment('Foto tampak depan');
            $table->string('photo_side_view')->nullable()->comment('Foto tampak samping');
            $table->string('photo_rear_view')->nullable()->comment('Foto tampak belakang');
            $table->string('photo_top_view')->nullable()->nullable()->comment('Foto tampak atas');
            
            // Measurement validation results
            $table->boolean('measurements_valid')->default(false);
            $table->text('measurement_notes')->nullable();
            
            // Overall Safety
            $table->boolean('unit_safe_for_loading')->default(false);
            $table->text('safety_notes')->nullable();
            
            // Critical Issues Count
            $table->integer('critical_issues_count')->default(0);
            $table->integer('warning_issues_count')->default(0);
            
            // Checked by
            $table->foreignId('checked_by')->constrained('users');
            $table->timestamp('checked_at');
            
            $table->timestamps();
            
            // Indexes
            $table->index('loading_session_id');
            $table->index('armada_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_checks');
    }
};
