<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sla_rules', function (Blueprint $table) {
            $table->id();

            $table->string('mode', 20); 
            $table->string('activity', 50);

            $table->foreignId('pol_id')->nullable()->constrained('ports')->nullOnDelete();
            $table->foreignId('pod_id')->nullable()->constrained('ports')->nullOnDelete();

            $table->unsignedSmallInteger('target_days');

            $table->boolean('is_active')->default(true);
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();

            $table->timestamps();

            $table->index(['mode', 'activity']);
            $table->index(['pol_id', 'pod_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sla_rules');
    }
};
