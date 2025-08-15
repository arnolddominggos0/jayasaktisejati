<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->unique()->nullable();
            $table->foreignId('office_id')->nullable()->constrained('offices')->nullOnDelete();
            // jika ada entitas customers: $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('office_id');
            // $table->dropConstrainedForeignId('customer_id');
            $table->dropColumn('username');
        });
    }
};
