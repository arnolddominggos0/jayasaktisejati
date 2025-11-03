<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('voyages', function (Blueprint $t) {
            if (!Schema::hasColumn('voyages', 'atd_at')) {
                $t->timestampTz('atd_at')->nullable()->after('eta');
            }
            if (!Schema::hasColumn('voyages', 'ata_at')) {
                $t->timestampTz('ata_at')->nullable()->after('atd_at');
            }
            $t->index(['atd_at', 'ata_at']);
        });
    }

    public function down(): void
    {
        Schema::table('voyages', function (Blueprint $t) {
            if (Schema::hasColumn('voyages', 'ata_at')) $t->dropColumn('ata_at');
            if (Schema::hasColumn('voyages', 'atd_at')) $t->dropColumn('atd_at');
        });
    }
};
