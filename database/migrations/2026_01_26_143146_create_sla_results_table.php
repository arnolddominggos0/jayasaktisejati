<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_results', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voyage_id')->constrained('voyages')->cascadeOnDelete();
            $table->foreignId('sla_rule_id')->constrained('sla_rules')->restrictOnDelete();

            $table->string('activity', 50); 
            $table->timestamp('start_at');
            $table->timestamp('end_at');

            $table->unsignedSmallInteger('target_days');
            $table->decimal('actual_days', 6, 2);

            $table->string('status', 20);
            $table->decimal('late_days', 6, 2)->default(0);

            $table->timestamps();

            $table->unique(['voyage_id', 'activity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_results');
    }
};
