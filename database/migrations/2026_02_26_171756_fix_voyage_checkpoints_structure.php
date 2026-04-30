<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyage_checkpoints', function (Blueprint $table) {

            if (!Schema::hasColumn('voyage_checkpoints', 'type')) {
                $table->string('type')->nullable();
            }

            if (!Schema::hasColumn('voyage_checkpoints', 'title')) {
                $table->string('title')->nullable();
            }

            if (!Schema::hasColumn('voyage_checkpoints', 'scheduled_at')) {
                $table->timestamp('scheduled_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('voyage_checkpoints', function (Blueprint $table) {
            $table->dropColumn(['type', 'title', 'scheduled_at']);
        });
    }
};
