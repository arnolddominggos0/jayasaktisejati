<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tam_monthly_schedules', function (Blueprint $table) {
            $table->id();
            $table->date('period_month');
            $table->string('version', 20)->default('v1.0');
            $table->string('status', 20)->default('draft');
            $table->unsignedInteger('total_plan')->default(0);
            $table->text('draft_message')->nullable();
            $table->string('draft_path')->nullable();
            $table->string('final_path')->nullable();
            $table->string('generated_by_name')->nullable();
            $table->timestamp('generated_at')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
            $table->unique('period_month');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tam_monthly_schedules');
    }
};
