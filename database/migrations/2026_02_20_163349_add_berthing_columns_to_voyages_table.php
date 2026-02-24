<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('voyages', 'etb')) {
            Schema::table('voyages', function (Blueprint $table) {
                $table->timestamp('etb')->nullable();
            });
        }

        if (!Schema::hasColumn('voyages', 'atb_at')) {
            Schema::table('voyages', function (Blueprint $table) {
                $table->timestamp('atb_at')->nullable();
            });
        }
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $table) {

            if (Schema::hasColumn('voyages', 'etb')) {
                $table->dropColumn('etb');
            }

            if (Schema::hasColumn('voyages', 'atb_at')) {
                $table->dropColumn('atb_at');
            }
        });
    }
};
