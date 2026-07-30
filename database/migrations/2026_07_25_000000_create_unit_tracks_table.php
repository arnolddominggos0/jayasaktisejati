<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('unit_tracks', function (Blueprint $t) {
            $t->id();
            $t->foreignId('unit_id')->constrained('units')->cascadeOnDelete();
            $t->timestamp('tracked_at')->nullable();
            $t->dateTime('plan_loading_time_at')->nullable();
            $t->dateTime('plan_closing_time_at')->nullable();
            $t->string('status')->nullable();
            $t->smallInteger('status_normalized')->default(0);
            $t->string('location')->nullable();
            $t->text('note')->nullable();
            $t->json('attachments')->nullable();
            $t->jsonb('check_result')->nullable();
            $t->json('checkseet')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['unit_id', 'tracked_at']);
            $t->index(['unit_id', 'status_normalized'], 'unit_tracks_unit_id_status_norm_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_tracks');
    }
};
