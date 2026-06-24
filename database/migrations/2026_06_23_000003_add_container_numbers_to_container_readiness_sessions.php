<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('container_readiness_sessions', function (Blueprint $table) {
            $table->json('container_numbers')
                ->nullable()
                ->after('notes')
                ->comment('Daftar nomor container fisik yang tersedia hari ini (TGHU1234567, ...)');
        });
    }

    public function down(): void
    {
        Schema::table('container_readiness_sessions', function (Blueprint $table) {
            $table->dropColumn('container_numbers');
        });
    }
};
