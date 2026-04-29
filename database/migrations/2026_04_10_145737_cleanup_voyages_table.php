<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('voyages', 'delay_reason')) {
            Schema::table('voyages', function (Blueprint $table) {
                $table->renameColumn('delay_reason', 'manual_delay_reason');
            });
        }

        Schema::table('voyages', function (Blueprint $table) {

            $columnsToDrop = [
                'dwelling_days',
                'kpi_sailing_days',
                'jss',
                'approved_by_name',
                'final_source',
                'final_attachment_path',
                'finalized_at',
                'is_delayed',
                'delay_reported_at',
                'rescheduled_etd',
                'rescheduled_eta',
                'is_final',
                'finalized_by',
                'finalized_by_name',
                'atb',
            ];

            foreach ($columnsToDrop as $column) {
                if (Schema::hasColumn('voyages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });

        DB::statement("
            ALTER TABLE voyages 
            ALTER COLUMN actual_sailing_days TYPE numeric(10,2)
        ");
    }

    public function down(): void
    {
        if (Schema::hasColumn('voyages', 'manual_delay_reason')) {
            Schema::table('voyages', function (Blueprint $table) {
                $table->renameColumn('manual_delay_reason', 'delay_reason');
            });
        }

        Schema::table('voyages', function (Blueprint $table) {

            if (!Schema::hasColumn('voyages', 'dwelling_days')) {
                $table->integer('dwelling_days')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'kpi_sailing_days')) {
                $table->integer('kpi_sailing_days')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'jss')) {
                $table->string('jss')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'approved_by_name')) {
                $table->string('approved_by_name')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'final_source')) {
                $table->string('final_source')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'final_attachment_path')) {
                $table->string('final_attachment_path')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'is_delayed')) {
                $table->boolean('is_delayed')->default(false);
            }

            if (!Schema::hasColumn('voyages', 'delay_reported_at')) {
                $table->timestamp('delay_reported_at')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'rescheduled_etd')) {
                $table->timestamp('rescheduled_etd')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'rescheduled_eta')) {
                $table->timestamp('rescheduled_eta')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'is_final')) {
                $table->boolean('is_final')->default(false);
            }

            if (!Schema::hasColumn('voyages', 'finalized_by')) {
                $table->bigInteger('finalized_by')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'finalized_by_name')) {
                $table->string('finalized_by_name')->nullable();
            }

            if (!Schema::hasColumn('voyages', 'atb')) {
                $table->timestamp('atb')->nullable();
            }
        });
    }
};