<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_checkpoints', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voyage_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code');
            $table->integer('offset_days')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('checked_at')->nullable();

            $table->string('status')->nullable();
            $table->text('note')->nullable();

            $table->string('checked_by')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_checkpoints');
    }
};
