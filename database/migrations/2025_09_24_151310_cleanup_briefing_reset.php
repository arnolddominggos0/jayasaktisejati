<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (Schema::hasTable('briefing_attendances')) {
            Schema::table('briefing_attendances', function (Blueprint $t) {
                if (Schema::hasColumn('briefing_attendances', 'team_id')) {
                    try {
                        $t->dropForeign(['team_id']);
                    } catch (\Throwable $e) {
                    }
                }
            });

            Schema::table('briefing_attendances', function (Blueprint $t) {
                if (Schema::hasColumn('briefing_attendances', 'team_id')) {
                    $t->dropColumn('team_id');
                }
                if (Schema::hasColumn('briefing_attendances', 'health_complaint')) {
                    $t->dropColumn('health_complaint');
                }
                if (Schema::hasColumn('briefing_attendances', 'ppe_details')) {
                    $t->dropColumn('ppe_details');
                }
                if (Schema::hasColumn('briefing_attendances', 'signature_path')) {
                    $t->dropColumn('signature_path');
                }
            });
        }

        if (Schema::hasTable('briefing_checklists')) {
            try {
                DB::statement("ALTER TABLE briefing_checklists DROP CONSTRAINT IF EXISTS briefing_checklists_item_check");
            } catch (\Throwable $e) {
            }
            Schema::dropIfExists('briefing_checklists');
        }

        if (Schema::hasTable('briefing_teams')) {
            Schema::dropIfExists('briefing_teams');
        }

        if (Schema::hasTable('manpower_attendances')) {
            Schema::dropIfExists('manpower_attendances');
        }

        if (Schema::hasTable('briefing_sessions')) {
            $exists = DB::selectOne("
                SELECT 1
                FROM pg_indexes
                WHERE schemaname = 'public'
                  AND tablename = 'briefing_sessions'
                  AND indexname = 'briefing_sessions_date_depot_id_unique'
            ");
            if (! $exists) {
                DB::statement("CREATE UNIQUE INDEX briefing_sessions_date_depot_id_unique ON briefing_sessions (date, depot_id)");
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('briefing_attendances')) {
            Schema::table('briefing_attendances', function (Blueprint $t) {
                if (! Schema::hasColumn('briefing_attendances', 'team_id')) {
                    $t->unsignedBigInteger('team_id')->nullable();
                }
                if (! Schema::hasColumn('briefing_attendances', 'health_complaint')) {
                    $t->text('health_complaint')->nullable();
                }
                if (! Schema::hasColumn('briefing_attendances', 'ppe_details')) {
                    $t->jsonb('ppe_details')->nullable();
                }
                if (! Schema::hasColumn('briefing_attendances', 'signature_path')) {
                    $t->string('signature_path')->nullable();
                }
            });
        }

        if (! Schema::hasTable('briefing_checklists')) {
            Schema::create('briefing_checklists', function (Blueprint $t) {
                $t->bigIncrements('id');
                $t->foreignId('session_id')->constrained('briefing_sessions')->cascadeOnDelete();
                $t->string('item', 100);
                $t->string('status', 32);
                $t->text('remark')->nullable();
                $t->timestamps();
                $t->unique(['session_id', 'item'], 'briefing_checklists_unique_item');
            });
        }

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
            });
        }
    }
};
