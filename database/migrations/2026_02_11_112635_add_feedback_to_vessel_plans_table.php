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
        Schema::table('vessel_plans', function (Blueprint $table) {
            $table->text('feedback_reason')->nullable();
            $table->foreignId('feedback_by')->nullable()->constrained('users');
            $table->timestamp('feedback_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vessel_plans', function (Blueprint $table) {
            //
        });
    }
};
