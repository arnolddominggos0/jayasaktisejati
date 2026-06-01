<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('vessel_plans', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('sent_by')->nullable();
            $table->text('tam_feedback_note')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('vessel_plans', function (Blueprint $table) {
            $table->dropColumn([
                'sent_at',
                'sent_by',
                'tam_feedback_note',
            ]);
        });
    }
};
