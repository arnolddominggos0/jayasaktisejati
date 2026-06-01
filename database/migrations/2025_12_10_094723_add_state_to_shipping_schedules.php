<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (! Schema::hasColumn('shipping_schedules', 'state')) {
                $table->string('state')->nullable()->after('voyage_id');
            }
            if (! Schema::hasColumn('shipping_schedules', 'finalized_by')) {
                $table->unsignedBigInteger('finalized_by')->nullable()->after('finalized_at');
            }
            if (! Schema::hasColumn('shipping_schedules', 'finalized_by_name')) {
                $table->string('finalized_by_name')->nullable()->after('finalized_by');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $table) {
            if (Schema::hasColumn('shipping_schedules', 'finalized_by_name')) {
                $table->dropColumn('finalized_by_name');
            }
            if (Schema::hasColumn('shipping_schedules', 'finalized_by')) {
                $table->dropColumn('finalized_by');
            }
            if (Schema::hasColumn('shipping_schedules', 'state')) {
                $table->dropColumn('state');
            }
        });
    }
};
