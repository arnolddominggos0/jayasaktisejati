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
        Schema::create('appsheet_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->string('sync_type'); // 'webhook', 'polling', 'manual'
            $table->string('table_name'); // nama table di AppSheet
            $table->string('record_id')->nullable(); // ID record yang di-sync
            $table->string('operation'); // 'create', 'update', 'delete'
            $table->json('payload')->nullable(); // data yang diterima
            $table->json('response')->nullable(); // response yang dikirim
            $table->string('status')->default('pending'); // 'success', 'failed', 'pending'
            $table->text('error_message')->nullable();
            $table->integer('retry_count')->default(0);
            $table->timestamp('processed_at')->nullable();
            $table->string('source')->nullable(); // 'appsheet', 'laravel'
            $table->string('synced_by')->nullable(); // user yang melakukan sync
            $table->timestamps();

            // Indexes
            $table->index('sync_type');
            $table->index('table_name');
            $table->index('record_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('appsheet_sync_logs');
    }
};
