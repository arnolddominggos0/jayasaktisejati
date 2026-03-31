<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('voyage_milestones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('voyage_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('code');
            // d4, d6, d8, d10, d12

            $table->date('milestone_date');

            $table->string('position')->nullable();
            $table->decimal('speed_knots', 5, 2)->nullable();
            $table->text('note')->nullable();

            $table->timestamps();

            $table->unique(['voyage_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voyage_milestones');
    }
};
