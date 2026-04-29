<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->boolean('is_delayed')->default(false)->after('actual_sailing_days');
            $table->text('delay_reason')->nullable()->after('is_delayed');
            $table->timestamp('delay_reported_at')->nullable()->after('delay_reason');
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropColumn(['is_delayed', 'delay_reason', 'delay_reported_at']);
        });
    }
};
