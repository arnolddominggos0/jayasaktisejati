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
    Schema::table('app_sheet_sync_logs', function (Blueprint $table) {

        if (! Schema::hasColumn('app_sheet_sync_logs', 'sync_type')) {
            $table->string('sync_type')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'table_name')) {
            $table->string('table_name')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'record_id')) {
            $table->string('record_id')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'operation')) {
            $table->string('operation')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'payload')) {
            $table->jsonb('payload')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'response')) {
            $table->jsonb('response')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'status')) {
            $table->string('status')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'error_message')) {
            $table->text('error_message')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'retry_count')) {
            $table->integer('retry_count')->default(0);
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'processed_at')) {
            $table->timestamp('processed_at')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'source')) {
            $table->string('source')->nullable();
        }

        if (! Schema::hasColumn('app_sheet_sync_logs', 'synced_by')) {
            $table->string('synced_by')->nullable();
        }
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
