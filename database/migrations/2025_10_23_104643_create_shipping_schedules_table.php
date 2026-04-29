<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shipping_schedules')) {
            Schema::create('shipping_schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
                $table->foreignId('shipping_line_id')->constrained()->cascadeOnDelete();
                $table->foreignId('vessel_id')->nullable()->constrained('vessels')->nullOnDelete();
                $table->string('vessel_name', 255)->nullable();
                $table->string('voyage_no', 50)->nullable()->index();
                $table->unsignedSmallInteger('cargo_plan')->default(0);
                $table->string('jss', 100)->nullable();
                $table->unsignedSmallInteger('dwelling_days')->nullable();
                $table->timestampTz('etd')->nullable()->index();
                $table->timestampTz('eta')->nullable()->index();
                $table->date('period_month')->nullable()->index();
                $table->string('state', 20)->default('draft')->index();
                $table->string('approved_by_name')->nullable();
                $table->text('final_note')->nullable();
                $table->string('final_source')->nullable();
                $table->string('final_attachment_path')->nullable();
                $table->timestampTz('finalized_at')->nullable();
                $table->unsignedSmallInteger('kpi_sailing_days')->nullable();
                $table->unsignedSmallInteger('actual_sailing_days')->nullable();
                $table->timestampsTz();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_schedules');
    }
};
