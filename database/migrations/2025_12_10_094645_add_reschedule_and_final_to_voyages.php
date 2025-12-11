<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (! Schema::hasColumn('voyages', 'rescheduled_etd')) {
                $table->timestamp('rescheduled_etd')->nullable()->after('delay_reported_at');
            }
            if (! Schema::hasColumn('voyages', 'rescheduled_eta')) {
                $table->timestamp('rescheduled_eta')->nullable()->after('rescheduled_etd');
            }
            if (! Schema::hasColumn('voyages', 'is_final')) {
                $table->boolean('is_final')->default(false)->after('rescheduled_eta');
            }
            if (! Schema::hasColumn('voyages', 'finalized_at')) {
                $table->timestamp('finalized_at')->nullable()->after('is_final');
            }
            if (! Schema::hasColumn('voyages', 'finalized_by')) {
                $table->unsignedBigInteger('finalized_by')->nullable()->after('finalized_at');
            }
            if (! Schema::hasColumn('voyages', 'finalized_by_name')) {
                $table->string('finalized_by_name')->nullable()->after('finalized_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            if (Schema::hasColumn('voyages', 'finalized_by_name')) {
                $table->dropColumn('finalized_by_name');
            }
            if (Schema::hasColumn('voyages', 'finalized_by')) {
                $table->dropColumn('finalized_by');
            }
            if (Schema::hasColumn('voyages', 'finalized_at')) {
                $table->dropColumn('finalized_at');
            }
            if (Schema::hasColumn('voyages', 'is_final')) {
                $table->dropColumn('is_final');
            }
            if (Schema::hasColumn('voyages', 'rescheduled_eta')) {
                $table->dropColumn('rescheduled_eta');
            }
            if (Schema::hasColumn('voyages', 'rescheduled_etd')) {
                $table->dropColumn('rescheduled_etd');
            }
        });
    }
};
