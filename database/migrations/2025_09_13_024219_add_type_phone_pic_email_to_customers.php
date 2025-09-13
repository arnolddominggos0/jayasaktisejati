<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasColumn('customers', 'type')) {
                $table->string('type', 20)->default('individual')->after('code');
            }
            if (!Schema::hasColumn('customers', 'phone')) {
                $table->string('phone', 30)->nullable()->after('email');
            }
            if (!Schema::hasColumn('customers', 'pic_email')) {
                $table->string('pic_email', 150)->nullable()->after('pic_phone');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (Schema::hasColumn('customers', 'type')) $table->dropColumn('type');
            if (Schema::hasColumn('customers', 'phone')) $table->dropColumn('phone');
            if (Schema::hasColumn('customers', 'pic_email')) $table->dropColumn('pic_email');
        });
    }
};
