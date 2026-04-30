<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {

            if (! Schema::hasColumn('voyages', 'etb')) {
                $table->timestampTz('etb')->nullable()->after('voyage_no');
            }

            if (! Schema::hasColumn('voyages', 'atb')) {
                $table->timestampTz('atb')->nullable()->after('etb');
            }

            if (! Schema::hasColumn('voyages', 'atd_at')) {
                $table->timestampTz('atd_at')->nullable()->after('atb');
            }

            if (! Schema::hasColumn('voyages', 'ata_at')) {
                $table->timestampTz('ata_at')->nullable()->after('atd_at');
            }

            if (! Schema::hasColumn('voyages', 'cargo_plan')) {
                $table->integer('cargo_plan')->default(0)->after('ata_at');
            }

            if (! Schema::hasColumn('voyages', 'cargo_actual')) {
                $table->integer('cargo_actual')->nullable()->after('cargo_plan');
            }

            if (! Schema::hasColumn('voyages', 'delay_reason')) {
                $table->string('delay_reason')->nullable()->after('cargo_actual');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (Schema::hasColumn('voyages', 'delay_reason')) {
                $table->dropColumn('delay_reason');
            }
            if (Schema::hasColumn('voyages', 'cargo_actual')) {
                $table->dropColumn('cargo_actual');
            }
            if (Schema::hasColumn('voyages', 'cargo_plan')) {
                $table->dropColumn('cargo_plan');
            }
            if (Schema::hasColumn('voyages', 'ata_at')) {
                $table->dropColumn('ata_at');
            }
            if (Schema::hasColumn('voyages', 'atd_at')) {
                $table->dropColumn('atd_at');
            }
            if (Schema::hasColumn('voyages', 'atb')) {
                $table->dropColumn('atb');
            }
            if (Schema::hasColumn('voyages', 'etb')) {
                $table->dropColumn('etb');
            }
        });
    }
};
