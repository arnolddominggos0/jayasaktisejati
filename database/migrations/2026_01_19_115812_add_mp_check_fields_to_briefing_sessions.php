<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->string('mp_check_status')
                ->default('draft')
                ->after('summary_solution');

            $table->timestamp('approved_at')
                ->nullable()
                ->after('mp_check_status');

            $table->foreignId('approved_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->after('approved_at');

            // Backup MP (SOP: MP backup / external)
            $table->boolean('backup_required')
                ->default(false)
                ->after('approved_by');

            $table->string('backup_type')
                ->nullable()
                ->after('backup_required'); // internal / external

            $table->text('backup_notes')
                ->nullable()
                ->after('backup_type');
        });
    }

    public function down(): void
    {
        Schema::table('briefing_sessions', function (Blueprint $table) {
            $table->dropColumn([
                'mp_check_status',
                'approved_at',
                'approved_by',
                'backup_required',
                'backup_type',
                'backup_notes',
            ]);
        });
    }
};
