<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('containers', function (Blueprint $table) {
            $table->id();

            $table->string('container_no', 32);

            $table->string('type', 20)->nullable();
            $table->unsignedSmallInteger('capacity')->nullable();

            $table->foreignId('container_readiness_session_id')
                ->constrained('container_readiness_sessions')
                ->cascadeOnDelete();
            $table->boolean('is_ready_for_stuffing')->default(false);
            $table->timestamp('marked_ready_at')->nullable();
            $table->foreignId('marked_ready_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->unique(['container_readiness_session_id', 'container_no'], 'containers_session_no_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('containers');
    }
};
