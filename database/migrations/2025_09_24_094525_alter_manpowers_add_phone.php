<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('manpowers', function (Blueprint $table) {
            if (! Schema::hasColumn('manpowers', 'phone')) {
                $table->string('phone', 30)->nullable()->after('certs');
            }
        });
    }

    public function down(): void
    {
        Schema::table('manpowers', function (Blueprint $table) {
            if (Schema::hasColumn('manpowers', 'phone')) {
                $table->dropColumn('phone');
            }
        });
    }
};
