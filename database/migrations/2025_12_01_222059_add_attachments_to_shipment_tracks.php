<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            $table->json('attachments')->nullable()->after('note');
            $table->string('check_result', 10)->nullable()->after('attachments');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipment_tracks', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'check_result']);
        });
    }
};
