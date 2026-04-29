<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (! Schema::hasColumn('voyages', 'period_month')) {
                $table->date('period_month')->nullable()->after('eta');
            }

            if (! Schema::hasColumn('voyages', 'cargo_plan')) {
                $table->integer('cargo_plan')->nullable()->after('period_month');
            }

            if (! Schema::hasColumn('voyages', 'dwelling_days')) {
                $table->integer('dwelling_days')->nullable()->after('cargo_plan');
            }

            if (! Schema::hasColumn('voyages', 'kpi_sailing_days')) {
                $table->integer('kpi_sailing_days')->nullable()->after('dwelling_days');
            }

            if (! Schema::hasColumn('voyages', 'actual_sailing_days')) {
                $table->integer('actual_sailing_days')->nullable()->after('kpi_sailing_days');
            }

            if (! Schema::hasColumn('voyages', 'jss')) {
                $table->string('jss', 100)->nullable()->after('actual_sailing_days');
            }

            if (! Schema::hasColumn('voyages', 'approved_by_name')) {
                $table->string('approved_by_name', 100)->nullable()->after('jss');
            }

            if (! Schema::hasColumn('voyages', 'final_note')) {
                $table->text('final_note')->nullable()->after('approved_by_name');
            }

            if (! Schema::hasColumn('voyages', 'final_source')) {
                $table->string('final_source', 100)->nullable()->after('final_note');
            }

            if (! Schema::hasColumn('voyages', 'final_attachment_path')) {
                $table->string('final_attachment_path', 255)->nullable()->after('final_source');
            }

            if (! Schema::hasColumn('voyages', 'finalized_at')) {
                $table->dateTime('finalized_at')->nullable()->after('final_attachment_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $drops = [
                'period_month',
                'cargo_plan',
                'dwelling_days',
                'kpi_sailing_days',
                'actual_sailing_days',
                'jss',
                'approved_by_name',
                'final_note',
                'final_source',
                'final_attachment_path',
                'finalized_at',
            ];

            foreach ($drops as $column) {
                if (Schema::hasColumn('voyages', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
