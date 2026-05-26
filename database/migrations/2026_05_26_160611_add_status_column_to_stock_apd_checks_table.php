<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_apd_checks', function (Blueprint $table) {

            $table->string('status')
                ->nullable()
                ->after('required_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('stock_apd_checks', function (Blueprint $table) {

            $table->dropColumn('status');
        });
    }
};
