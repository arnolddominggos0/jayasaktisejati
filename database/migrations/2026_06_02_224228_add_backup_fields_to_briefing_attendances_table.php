<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            $table->string('mp_type')
                ->default('regular')
                ->after('manpower_id');

            $table->string('backup_name')
                ->nullable()
                ->after('mp_type');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            $table->dropColumn([
                'mp_type',
                'backup_name',
            ]);
        });
    }
};
