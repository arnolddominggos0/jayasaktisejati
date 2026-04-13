<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vessel_plan_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_plan_id')->constrained()->cascadeOnDelete();
            $table->string('stage', 32);
            $table->json('schedule_payload');
            $table->json('kpi_payload');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['vessel_plan_id', 'stage']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vessel_plan_snapshots');
    }
};
