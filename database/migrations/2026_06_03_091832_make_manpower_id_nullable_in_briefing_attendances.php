<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            $table->unsignedBigInteger('manpower_id')
                ->nullable()
                ->change();

        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            $table->unsignedBigInteger('manpower_id')
                ->nullable(false)
                ->change();

        });
    }
};
