<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->string('stuffing_status', 20)->default('ready')->after('is_ready_for_stuffing');
            $table->timestamp('stuffing_started_at')->nullable()->after('stuffing_status');
            $table->timestamp('stuffing_completed_at')->nullable()->after('stuffing_started_at');
        });
    }

    public function down(): void
    {
        Schema::table('containers', function (Blueprint $table) {
            $table->dropColumn(['stuffing_status', 'stuffing_started_at', 'stuffing_completed_at']);
        });
    }
};
