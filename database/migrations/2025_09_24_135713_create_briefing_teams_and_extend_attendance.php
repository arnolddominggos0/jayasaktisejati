<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('briefing_teams')) {
            Schema::create('briefing_teams', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('session_id')->constrained('briefing_sessions')->cascadeOnDelete();

                $t->foreignId('pic_user_id')->nullable()->constrained('users')->nullOnDelete();
                $t->string('pic_name')->nullable();

                $t->unsignedInteger('reported_headcount')->nullable(); 
                $t->boolean('is_sufficient')->nullable();               
                $t->text('solution_note')->nullable();                  

                $t->timestamps();

                $t->index(['session_id'], 'briefing_teams_session_idx');
            });
        }

        if (Schema::hasTable('briefing_attendances')) {
            Schema::table('briefing_attendances', function (Blueprint $t) {
                if (! Schema::hasColumn('briefing_attendances', 'team_id')) {
                    $t->foreignId('team_id')->nullable()->after('session_id')
                        ->constrained('briefing_teams')->nullOnDelete();
                }
                if (! Schema::hasColumn('briefing_attendances', 'health_complaint')) {
                    $t->text('health_complaint')->nullable()->after('bp');
                }
                if (! Schema::hasColumn('briefing_attendances', 'ppe_details')) {
                    $t->jsonb('ppe_details')->nullable()->after('has_ppe');
                }
                if (! Schema::hasColumn('briefing_attendances', 'signature_path')) {
                    $t->string('signature_path')->nullable()->after('remark'); 
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('briefing_attendances')) {
            Schema::table('briefing_attendances', function (Blueprint $t) {
                if (Schema::hasColumn('briefing_attendances', 'signature_path')) $t->dropColumn('signature_path');
                if (Schema::hasColumn('briefing_attendances', 'ppe_details'))    $t->dropColumn('ppe_details');
                if (Schema::hasColumn('briefing_attendances', 'health_complaint')) $t->dropColumn('health_complaint');
                if (Schema::hasColumn('briefing_attendances', 'team_id'))        $t->dropConstrainedForeignId('team_id');
            });
        }
        Schema::dropIfExists('briefing_teams');
    }
};
