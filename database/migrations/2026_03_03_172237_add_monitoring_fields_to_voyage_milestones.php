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
        Schema::table('voyage_milestones', function (Blueprint $table) {
            $table->dateTime('actual_date')->nullable()->after('milestone_date');
            $table->string('location_note')->nullable()->after('actual_date');
            $table->string('status')->nullable()->after('location_note');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('voyage_milestones', function (Blueprint $table) {
            //
        });
    }
};
