<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;


return new class extends Migration
{
    public function up(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->timestamp('stuffed_at')->nullable()->after('allocation_status');
            $table->foreignId('stuffed_by')->nullable()->after('stuffed_at')->constrained('users')->nullOnDelete();
            $table->text('stuffing_remarks')->nullable()->after('stuffed_by');
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropConstrainedForeignId('stuffed_by');
            $table->dropColumn(['stuffed_at', 'stuffing_remarks']);
        });
    }
};
