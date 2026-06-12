<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->foreignId('checked_by')
                ->nullable()
                ->after('source')
                ->constrained('users')
                ->nullOnDelete();

            // Gate decision sesuai SOP TAM
            // accept | allow_with_remark | return_to_pdc
            // null = belum disubmit (masih draft)
            $table->string('gate_decision', 30)
                ->nullable()
                ->after('checked_by');

            $table->timestamp('submitted_at')
                ->nullable()
                ->after('gate_decision');

            $table->index('gate_decision');
        });
    }

    public function down(): void
    {
        Schema::table('unit_inspections', function (Blueprint $table) {
            $table->dropForeign(['checked_by']);
            $table->dropIndex(['unit_inspections_gate_decision_index']);
            $table->dropColumn(['checked_by', 'gate_decision', 'submitted_at']);
        });
    }
};
