<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('scope_branch_id')->nullable()->index()->after('branch_id');
            $table->unsignedBigInteger('scope_unit_id')->nullable()->index()->after('scope_branch_id');
            $table->string('scope_unit_type', 16)->nullable()->after('scope_unit_id');

            $table->foreign('scope_branch_id')
                ->references('id')
                ->on('branches')
                ->nullOnDelete();
        });

        // Partial unique index on pools.coordinator_user_id (PostgreSQL only).
        // MySQL does not support partial indexes; fallback to a regular unique.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE UNIQUE INDEX pools_coordinator_unique ON pools (coordinator_user_id) WHERE coordinator_user_id IS NOT NULL;');
        } else {
            Schema::table('pools', function (Blueprint $table) {
                $table->unique('coordinator_user_id');
            });
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS pools_coordinator_unique;');
        } else {
            Schema::table('pools', function (Blueprint $table) {
                $table->dropUnique(['coordinator_user_id']);
            });
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['scope_branch_id']);
            $table->dropColumn(['scope_branch_id', 'scope_unit_id', 'scope_unit_type']);
        });
    }
};
