<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            if (! Schema::hasColumn('depots', 'port_id')) {
                $table->foreignId('port_id')->nullable()->constrained('ports')->nullOnDelete()->after('mode');
                $table->index(['branch_id', 'mode', 'port_id']);
            }
            $table->index(['coordinator_user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('depots', function (Blueprint $table) {
            if (Schema::hasColumn('depots', 'port_id')) {
                $table->dropConstrainedForeignId('port_id');
            }
            $table->dropIndex(['depots_branch_id_mode_port_id_index']);
            $table->dropIndex(['depots_coordinator_user_id_index']);
        });
    }
};
