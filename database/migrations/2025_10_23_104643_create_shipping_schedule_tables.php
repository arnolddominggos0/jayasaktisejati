<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('voyage_id')->unique()->constrained()->cascadeOnDelete();
            $table->unsignedInteger('cargo_plan')->default(0);
            $table->string('state')->default('draft');
            $table->string('approved_by_name')->nullable();
            $table->text('final_note')->nullable();
            $table->string('final_source')->nullable();
            $table->string('final_attachment_path')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('shipping_schedules');
    }
};
