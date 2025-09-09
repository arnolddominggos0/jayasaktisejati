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
        Schema::table('shipments', function (Blueprint $t) {
            if (! Schema::hasColumn('shipments', 'created_at')) {
                $t->timestampTz('created_at')->nullable()->useCurrent();
            }
            if (! Schema::hasColumn('shipments', 'updated_at')) {
                $t->timestampTz('updated_at')->nullable()->useCurrent();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('shipments')) return;

        Schema::table('shipments', function (Blueprint $table) {
            if (Schema::hasColumn('shipments', 'updated_at')) {
                $table->dropColumn('updated_at');
            }
            if (Schema::hasColumn('shipments', 'created_at')) {
                $table->dropColumn('created_at'); 
            }
        });
    }
};
