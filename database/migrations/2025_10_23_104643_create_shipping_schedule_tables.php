<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('shipping_schedules')) {
            Schema::create('shipping_schedules', function (Blueprint $t) {
                $t->id();
                $t->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
                $t->foreignId('shipping_line_id')->nullable()->constrained('shipping_lines')->nullOnDelete();
                $t->foreignId('vessel_id')->nullable()->constrained('vessels')->nullOnDelete();
                $t->string('vessel_name', 255)->nullable();
                $t->string('voyage_no', 50)->nullable()->index();
                $t->unsignedSmallInteger('cargo_plan')->default(0);
                $t->string('jss', 100)->nullable();
                $t->unsignedSmallInteger('dwelling_days')->nullable();
                $t->timestampTz('etd')->nullable()->index();
                $t->timestampTz('eta')->nullable()->index();
                $t->date('period_month')->nullable()->index();
                $t->string('state', 20)->default('draft')->index();
                $t->string('approved_by_name')->nullable();
                $t->text('final_note')->nullable();
                $t->string('final_source')->nullable();
                $t->string('final_attachment_path')->nullable();
                $t->timestampTz('finalized_at')->nullable();
                $t->unsignedSmallInteger('kpi_sailing_days')->nullable();
                $t->unsignedSmallInteger('actual_sailing_days')->nullable();
                $t->timestampsTz();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_schedules');
    }
};
