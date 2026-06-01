<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->foreignId('shipping_line_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete()
                ->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {
            $table->dropForeign(['shipping_line_id']);
            $table->dropColumn('shipping_line_id');
        });
    }
};
