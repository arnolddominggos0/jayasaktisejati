<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->string('signed_by')->nullable()->after('notes');
            $table->timestamp('signed_at')->nullable()->after('signed_by');
            $table->string('signature_path')->nullable()->after('signed_at');
        });
    }

    public function down(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->dropColumn(['signed_by', 'signed_at', 'signature_path']);
        });
    }
};
