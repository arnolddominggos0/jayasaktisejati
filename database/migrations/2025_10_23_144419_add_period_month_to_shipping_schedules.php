<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $t) {
            if (!Schema::hasColumn('shipping_schedules', 'period_month')) {
                $t->date('period_month')->nullable()->index()
                    ->comment('First day of month for archiving/filtering');
            }
        });
    }
    public function down(): void
    {
        Schema::table('shipping_schedules', function (Blueprint $t) {
            if (Schema::hasColumn('shipping_schedules', 'period_month')) {
                $t->dropColumn('period_month');
            }
        });
    }
};
