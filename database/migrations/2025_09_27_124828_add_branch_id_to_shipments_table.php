<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'branch_id')) {
                $table->foreignId('branch_id')->nullable()->constrained('branches');
            }
            if (!Schema::hasColumn('shipments', 'coordinator_id')) {
                $table->foreignId('coordinator_id')->nullable()->constrained('users');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'coordinator_id')) {
                $table->dropConstrainedForeignId('coordinator_id');
            }
            if (Schema::hasColumn('shipments', 'branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
    }
};
