<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_inspection_items', function (Blueprint $table) {
            // Jenis temuan — menentukan gate decision di level header
            // null jika result = ok
            // major_damage | minor_missing | information_only
            $table->string('finding_type', 20)
                ->nullable()
                ->after('result');

            $table->string('photo_url', 500)
                ->nullable()
                ->after('notes');

            $table->index('finding_type');
        });
    }

    public function down(): void
    {
        Schema::table('unit_inspection_items', function (Blueprint $table) {
            $table->dropIndex(['unit_inspection_items_finding_type_index']);
            $table->dropColumn(['finding_type', 'photo_url']);
        });
    }
};
