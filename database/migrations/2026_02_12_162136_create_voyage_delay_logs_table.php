<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_delay_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->timestamp('old_etd')->nullable();
            $table->timestamp('new_etd')->nullable();

            $table->timestamp('old_eta')->nullable();
            $table->timestamp('new_eta')->nullable();

            $table->string('reason')->nullable();
            $table->string('changed_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_delay_logs');
    }
};
