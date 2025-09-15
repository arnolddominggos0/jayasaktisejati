<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('vessels', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index();
            $table->foreignId('shipping_line_id')->constrained('shipping_lines')->cascadeOnDelete();
            $table->string('imo')->nullable();
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('vessels'); }
};
