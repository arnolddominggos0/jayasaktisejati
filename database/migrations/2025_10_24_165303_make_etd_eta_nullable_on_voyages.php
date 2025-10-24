<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->timestampTz('etd')->nullable()->change();
            $table->timestampTz('eta')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->timestampTz('etd')->nullable(false)->change();
            $table->timestampTz('eta')->nullable(false)->change();
        });
    }
};
