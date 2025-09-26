<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('ppe_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ppe_sku_id')->constrained('ppe_skus')->cascadeOnDelete();
            $table->string('serial')->nullable()->index();
            $table->string('status')->default('in_stock');
            $table->unsignedBigInteger('current_manpower_id')->nullable()->index();
            $table->timestamp('assigned_at')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void
    {
        Schema::dropIfExists('ppe_items');
    }
};
