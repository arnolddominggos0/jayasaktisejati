<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('shipments', 'assigned_depot_id')) {
                $table->foreignId('assigned_depot_id')
                    ->nullable()
                    ->constrained('depots')
                    ->nullOnDelete()
                    ->after('coordinator_id');
                $table->index(['branch_id', 'mode', 'assigned_depot_id']);
            }
        });

        DB::statement("
            UPDATE shipments AS s
               SET assigned_depot_id = d.id
              FROM depots AS d, voyages AS v
             WHERE s.mode = 'sea'
               AND s.assigned_depot_id IS NULL
               AND d.mode = 'sea'
               AND d.branch_id = s.branch_id
               AND v.id = s.voyage_id
               AND d.port_id = v.port_from_id
        ");

        DB::statement("
            UPDATE shipments AS s
               SET assigned_depot_id = d.id
              FROM depots AS d, voyages AS v
             WHERE s.mode = 'sea'
               AND s.assigned_depot_id IS NULL
               AND d.mode = 'sea'
               AND d.branch_id = s.branch_id
               AND v.id = s.voyage_id
               AND d.port_id = v.port_to_id
        ");

        DB::unprepared("
            ALTER TABLE shipments
            ADD CONSTRAINT chk_shipments_depot_sea_only
            CHECK (
                (mode = 'sea'  AND assigned_depot_id IS NOT NULL)
                OR
                (mode <> 'sea' AND assigned_depot_id IS NULL)
            ) NOT VALID
        ");
    }

    public function down(): void
    {
        DB::unprepared("ALTER TABLE shipments DROP CONSTRAINT IF EXISTS chk_shipments_depot_sea_only;");

        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'assigned_depot_id')) {
                $table->dropConstrainedForeignId('assigned_depot_id');
            }
            $table->dropIndex(['shipments_branch_id_mode_assigned_depot_id_index']);
        });

        DB::statement("
            UPDATE shipments AS s
               SET assigned_depot_id = NULL
              FROM depots AS d, voyages AS v
             WHERE s.mode = 'sea'
               AND d.id = s.assigned_depot_id
               AND d.mode = 'sea'
               AND d.branch_id = s.branch_id
               AND v.id = s.voyage_id
               AND (d.port_id = v.port_from_id OR d.port_id = v.port_to_id)
        ");
    }
};
