<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('shipping_schedules', 'pol_id')) {
                $table->foreignId('pol_id')
                    ->nullable()
                    ->after('shipping_line_id')
                    ->constrained('ports');
            }

            if (! Schema::hasColumn('shipping_schedules', 'pod_id')) {
                $table->foreignId('pod_id')
                    ->nullable()
                    ->after('pol_id')
                    ->constrained('ports');
            }

            if (! Schema::hasColumn('shipping_schedules', 'is_tam')) {
                $table->boolean('is_tam')
                    ->default(false)
                    ->after('pod_id')
                    ->index();
            }

            if (! Schema::hasColumn('shipping_schedules', 'period_month')) {
                $table->date('period_month')
                    ->nullable()
                    ->after('eta')
                    ->index();
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_schedules', 'pol_id')) {
                $table->dropConstrainedForeignId('pol_id');
            }

            if (Schema::hasColumn('shipping_schedules', 'pod_id')) {
                $table->dropConstrainedForeignId('pod_id');
            }

            if (Schema::hasColumn('shipping_schedules', 'is_tam')) {
                $table->dropColumn('is_tam');
            }

            if (Schema::hasColumn('shipping_schedules', 'period_month')) {
                $table->dropColumn('period_month');
            }
        });
    }
};
