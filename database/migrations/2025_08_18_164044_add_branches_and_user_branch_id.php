<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasTable('branches')) {
            Schema::create('branches', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users','branch_id')) {
                $table->foreignId('branch_id')->nullable()->after('id')
                      ->constrained('branches')->nullOnDelete();
            }
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users','branch_id')) {
                $table->dropConstrainedForeignId('branch_id');
            }
        });
        Schema::dropIfExists('branches');
    }
};
