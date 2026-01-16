<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('pol_id')->nullable()->constrained('ports')->after('pod');
            $table->foreignId('pod_id')->nullable()->constrained('ports')->after('pol_id');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('pol_id');
            $table->dropColumn('pod_id');
        });
    }
};
