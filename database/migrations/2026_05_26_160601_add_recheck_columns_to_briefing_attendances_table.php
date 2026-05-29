<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            // fit status
            $table->string('fit_status')
                ->nullable();

            // recheck
            $table->boolean('recheck_required')
                ->default(false);

            // medical
            $table->string('medical_action')
                ->nullable();

            // APD personal
            $table->string('personal_ppe_status')
                ->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('briefing_attendances', function (Blueprint $table) {

            $table->dropColumn([
                'fit_status',
                'recheck_required',
                'medical_action',
                'personal_ppe_status',
            ]);
        });
    }
};
