<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('armada_status_logs')) {
            Schema::create('armada_status_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('armada_id')->constrained('armadas')->cascadeOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->text('reason')->nullable();
                $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('changed_at')->useCurrent();
                $table->timestamps();
                $table->index(['armada_id', 'changed_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('armada_status_logs');
    }
};
