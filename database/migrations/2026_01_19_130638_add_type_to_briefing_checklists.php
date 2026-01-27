<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('briefing_checklists')) {
            Schema::create('briefing_checklists', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->foreignId('session_id')->constrained('briefing_sessions')->cascadeOnDelete();

                $table->string('item', 100);        
                $table->string('status', 32);        
                $table->text('remark')->nullable();

                $table->timestamps();

                $table->unique(['session_id', 'item'], 'briefing_checklists_unique_item');
                $table->index('session_id', 'briefing_checklists_session_idx');
                $table->index('status', 'briefing_checklists_status_idx');
            });
        }
        Schema::table('briefing_checklists', function (Blueprint $table) {
            $table->string('type')
                ->default('briefing')
                ->after('item');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_checklists', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
