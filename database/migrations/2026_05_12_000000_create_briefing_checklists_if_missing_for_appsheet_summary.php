<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('briefing_checklists')) {
            Schema::create('briefing_checklists', function (Blueprint $table) {
                $table->id();
                $table->foreignId('session_id')->constrained('briefing_sessions')->cascadeOnDelete();
                $table->string('item', 100);
                $table->string('type', 32)->default('briefing');
                $table->string('status', 32);
                $table->text('remark')->nullable();
                $table->timestamps();

                $table->unique(['session_id', 'item'], 'briefing_checklists_unique_item');
                $table->index('session_id', 'briefing_checklists_session_idx');
                $table->index('status', 'briefing_checklists_status_idx');
            });

            return;
        }

        Schema::table('briefing_checklists', function (Blueprint $table) {
            if (! Schema::hasColumn('briefing_checklists', 'type')) {
                $table->string('type', 32)->default('briefing')->after('item');
            }
        });
    }

    public function down(): void
    {
        // No-op: this migration repairs an AppSheet table that may already exist in deployed databases.
    }
};
