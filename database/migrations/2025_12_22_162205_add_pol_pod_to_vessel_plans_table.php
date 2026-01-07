<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vessel_plans', function (Blueprint $table) {
            $table->foreignId('pol_id')
                ->nullable()
                ->after('period_month')
                ->constrained('ports');

            $table->foreignId('pod_id')
                ->nullable()
                ->after('pol_id')
                ->constrained('ports');

            $table->index(['period_month', 'pol_id', 'pod_id']);
        });
    }

    public function down(): void
    {
        Schema::table('vessel_plans', function (Blueprint $table) {
            $table->dropForeign(['pol_id']);
            $table->dropForeign(['pod_id']);

            $table->dropColumn(['pol_id', 'pod_id']);
        });
    }
};
