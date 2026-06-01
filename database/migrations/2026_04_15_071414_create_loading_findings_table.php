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
        Schema::create('loading_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loading_session_id')->constrained('loading_sessions')->cascadeOnDelete();
            
            // Finding categorization
            $table->string('category'); // rack_pillar, drop_floor, equipment, apd, health, unit, other
            $table->string('severity'); // critical, warning, info
            $table->string('item_name'); // Name of the item with issue
            $table->string('finding_type'); // damaged, missing, unsafe, dirty, etc.
            
            // Finding details
            $table->text('description');
            $table->text('corrective_action')->nullable();
            $table->string('photo')->nullable();
            
            // Status tracking
            $table->string('status')->default('open'); // open, in_progress, resolved, closed
            $table->timestamp('resolved_at')->nullable();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('resolution_notes')->nullable();
            
            // Escalation
            $table->boolean('escalated')->default(false);
            $table->foreignId('escalated_to')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('escalated_at')->nullable();
            
            // Timestamps
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            
            // Indexes
            $table->index('loading_session_id');
            $table->index('category');
            $table->index('severity');
            $table->index('status');
            $table->index(['loading_session_id', 'severity']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loading_findings');
    }
};
