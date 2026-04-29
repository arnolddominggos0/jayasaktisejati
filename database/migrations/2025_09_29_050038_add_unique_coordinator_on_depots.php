<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX depots_coordinator_unique ON depots (coordinator_user_id) WHERE coordinator_user_id IS NOT NULL;');
        } else {
            Schema::table('depots', fn (Blueprint $t) => $t->unique('coordinator_user_id'));
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS depots_coordinator_unique;');
        } else {
            Schema::table('depots', fn (Blueprint $t) => $t->dropUnique(['coordinator_user_id']));
        }
    }
};
