<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->date('period_month')->nullable()->after('eta');
        });

        DB::statement("
            update voyages
            set period_month = date_trunc('month', etd)::date
            where etd is not null
        ");
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropColumn('period_month');
        });
    }
};
