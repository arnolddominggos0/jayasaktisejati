<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tam_monthly_schedules', function (Blueprint $t) {
            $t->id();
            $t->date('period_month');
            $t->string('status')->default('draft');
            $t->string('version')->default('v1.0');
            $t->jsonb('payload')->nullable();
            $t->jsonb('schedule_ids')->nullable();
            $t->unsignedBigInteger('total_plan')->default(0);
            $t->string('draft_path')->nullable();
            $t->string('final_path')->nullable();
            $t->text('draft_message')->nullable();
            $t->timestamp('generated_at')->nullable();
            $t->timestamp('sent_at')->nullable();
            $t->timestamp('feedback_received_at')->nullable();
            $t->timestamp('finalized_at')->nullable();
            $t->string('generated_by_name')->nullable();
            $t->string('approved_by_name')->nullable();
            $t->timestamps();
            $t->unique(['period_month']);
            $t->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tam_monthly_schedules');
    }
};
