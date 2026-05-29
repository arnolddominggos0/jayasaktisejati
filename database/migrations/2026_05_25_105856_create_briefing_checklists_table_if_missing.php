<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('briefing_checklists')) {

            Schema::create('briefing_checklists', function (Blueprint $table) {
                $table->id();

                $table->foreignId('session_id')
                    ->constrained('briefing_sessions')
                    ->cascadeOnDelete();

                $table->string('item');

                $table->string('type')->nullable();

                $table->string('status')->nullable();

                $table->text('remark')->nullable();

                $table->timestamps();

                $table->unique(['session_id', 'item']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('briefing_checklists');
    }
};
