<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vessel_checks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipping_schedule_id')
                ->constrained('shipping_schedules')
                ->cascadeOnDelete();

            $table->date('check_date');              // tanggal monitoring
            $table->string('day_code', 5);            // D-3, D-2, D-1

            $table->dateTime('etd_plan')->nullable();    // snapshot plan
            $table->dateTime('etd_current')->nullable(); // update ETD

            $table->string('status', 30)->default('on_schedule');
            // on_schedule | potential_delay | delayed

            $table->text('delay_reason')->nullable();
            $table->text('note')->nullable();

            $table->string('source')->nullable(); // WA / Tantolink / Manual
            $table->foreignId('created_by')->nullable();

            $table->timestamps();

            $table->unique([
                'shipping_schedule_id',
                'check_date',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_checks');
    }
};
