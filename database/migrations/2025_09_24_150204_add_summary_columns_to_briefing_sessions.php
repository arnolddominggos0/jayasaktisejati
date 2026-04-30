<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('briefing_sessions')) {
            Schema::table('briefing_sessions', function (Blueprint $table) {
                if (! Schema::hasColumn('briefing_sessions', 'summary_headcount')) {
                    $table->unsignedInteger('summary_headcount')->nullable()->after('notes');
                }
                if (! Schema::hasColumn('briefing_sessions', 'summary_sufficient')) {
                    $table->boolean('summary_sufficient')->nullable()->after('summary_headcount');
                }
                if (! Schema::hasColumn('briefing_sessions', 'summary_solution')) {
                    $table->text('summary_solution')->nullable()->after('summary_sufficient');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('briefing_sessions')) {
            Schema::table('briefing_sessions', function (Blueprint $table) {
                if (Schema::hasColumn('briefing_sessions', 'summary_solution')) {
                    $table->dropColumn('summary_solution');
                }
                if (Schema::hasColumn('briefing_sessions', 'summary_sufficient')) {
                    $table->dropColumn('summary_sufficient');
                }
                if (Schema::hasColumn('briefing_sessions', 'summary_headcount')) {
                    $table->dropColumn('summary_headcount');
                }
            });
        }
    }
};
