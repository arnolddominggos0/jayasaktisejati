<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_schedules', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->string('state', 20)->default('draft');
            $table->timestampTz('etd')->nullable();
            $table->timestampTz('eta')->nullable();
            $table->string('vessel_name')->nullable();
            $table->string('voyage_no')->nullable();
            $table->integer('cargo_plan_total')->nullable();
            $table->string('final_source', 20)->nullable();
            $table->string('final_attachment_path')->nullable();
            $table->text('final_note')->nullable();
            $table->string('approved_by_name', 120)->nullable();
            $table->timestampTz('approved_at')->nullable();
            $table->string('final_email_message_id')->nullable();
            $table->string('final_email_subject')->nullable();
            $table->string('final_email_from')->nullable();
            $table->timestampTz('final_email_received_at')->nullable();
            $table->smallInteger('revision_count')->default(0);
            $table->timestampTz('last_revision_at')->nullable();
            $table->timestamps();
            $table->index(['state']);
            $table->index(['etd']);
            $table->index(['eta']);
            $table->index(['vessel_name']);
            $table->index(['voyage_no']);
        });

        Schema::create('shipping_schedule_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_schedule_id')->constrained('shipping_schedules')->cascadeOnDelete();
            $table->timestampTz('etd')->nullable();
            $table->timestampTz('eta')->nullable();
            $table->string('vessel_name')->nullable();
            $table->integer('vessel_capacity')->nullable();
            $table->string('voyage_no')->nullable();
            $table->integer('cargo_plan')->nullable();
            $table->string('jss', 50)->nullable();
            $table->integer('dwelling')->nullable();
            $table->string('service')->nullable();
            $table->timestamps();
            $table->index(['shipping_schedule_id']);
            $table->index(['etd']);
            $table->index(['eta']);
            $table->index(['voyage_no']);
            $table->index(['vessel_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_schedule_items');
        Schema::dropIfExists('shipping_schedules');
    }
};
