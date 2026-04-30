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
        Schema::create('loading_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_session_id')->constrained('loading_sessions')->cascadeOnDelete();
            
            // Photo categorization
            $table->string('category'); // mp_attendance, health_check, apd, equipment, rack_pillar, drop_floor, container_structure, unit, final, other
            $table->string('sub_category')->nullable(); // e.g., pillar_a, pillar_b, pulley_top, etc.
            
            // Photo details
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->integer('file_size')->nullable(); // in bytes
            
            // GPS metadata
            $table->decimal('gps_latitude', 10, 8)->nullable();
            $table->decimal('gps_longitude', 11, 8)->nullable();
            $table->timestamp('taken_at')->nullable();
            
            // Description
            $table->text('description')->nullable();
            
            // Uploader
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('loading_session_id');
            $table->index('category');
            $table->index(['loading_session_id', 'category']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_photos');
    }
};
