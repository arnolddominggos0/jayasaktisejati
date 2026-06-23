<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->string('route_code', 20)->nullable()->index()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropIndex(['route_code']);
            $table->dropColumn('route_code');
        });
    }
};
