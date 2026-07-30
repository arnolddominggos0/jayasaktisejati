<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->string('signed_position')->nullable()->after('signed_by');
        });
    }

    public function down(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->dropColumn('signed_position');
        });
    }
};
