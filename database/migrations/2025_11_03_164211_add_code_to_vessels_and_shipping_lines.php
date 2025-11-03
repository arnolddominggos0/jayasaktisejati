<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vessels', function (Blueprint $t) {
            if (!Schema::hasColumn('vessels', 'code')) {
                $t->string('code', 8)->nullable()->index()->after('name');
            }
        });
        Schema::table('shipping_lines', function (Blueprint $t) {
            if (!Schema::hasColumn('shipping_lines', 'code')) {
                $t->string('code', 8)->nullable()->index()->after('name');
            }
        });
    }
    public function down(): void
    {
        Schema::table('vessels', fn(Blueprint $t) => $t->dropColumn('code'));
        Schema::table('shipping_lines', fn(Blueprint $t) => $t->dropColumn('code'));
    }
};
