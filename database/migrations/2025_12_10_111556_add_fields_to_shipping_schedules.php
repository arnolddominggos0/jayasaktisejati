<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('shipping_schedules', 'voyage_id')) {
                $table->foreignId('voyage_id')->nullable()->constrained('voyages')->nullOnDelete()->after('id');
            }
            if (! Schema::hasColumn('shipping_schedules', 'shipping_line_id')) {
                $table->unsignedBigInteger('shipping_line_id')->nullable()->after('voyage_id');
            }
            if (! Schema::hasColumn('shipping_schedules', 'vessel_id')) {
                $table->unsignedBigInteger('vessel_id')->nullable()->after('shipping_line_id');
            }
            if (! Schema::hasColumn('shipping_schedules', 'pol_id')) {
                $table->unsignedBigInteger('pol_id')->nullable()->after('vessel_id');
            }
            if (! Schema::hasColumn('shipping_schedules', 'pod_id')) {
                $table->unsignedBigInteger('pod_id')->nullable()->after('pol_id');
            }
            if (! Schema::hasColumn('shipping_schedules', 'voyage_no')) {
                $table->string('voyage_no')->nullable()->after('pod_id');
            }
            if (! Schema::hasColumn('shipping_schedules', 'etd')) {
                $table->timestamp('etd')->nullable()->after('voyage_no');
            }
            if (! Schema::hasColumn('shipping_schedules', 'eta')) {
                $table->timestamp('eta')->nullable()->after('etd');
            }
            if (! Schema::hasColumn('shipping_schedules', 'cargo_plan')) {
                $table->integer('cargo_plan')->nullable()->after('eta');
            }
            if (! Schema::hasColumn('shipping_schedules', 'cargo_actual')) {
                $table->integer('cargo_actual')->nullable()->after('cargo_plan');
            }
            if (! Schema::hasColumn('shipping_schedules', 'vessel_name')) {
                $table->string('vessel_name')->nullable()->after('cargo_actual');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            foreach ([
                'vessel_name','cargo_actual','cargo_plan','eta','etd','voyage_no','pod_id','pol_id','vessel_id','shipping_line_id','voyage_id'
            ] as $col) {
                if (Schema::hasColumn('shipping_schedules', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
