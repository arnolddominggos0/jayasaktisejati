<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('voyage_delay_logs', function (Blueprint $table) {
            $table->timestamp('new_etb')->nullable();
            $table->timestamp('new_atb_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('voyage_delay_logs', function (Blueprint $table) {
            $table->dropColumn(['new_etb', 'new_atb_at']);
        });
    }
};
