<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('unit_inspection_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('unit_inspection_id')
                ->constrained('unit_inspections')
                ->cascadeOnDelete();

            // Grup item — EXTERIOR, INTERIOR, DOCUMENT, ACCESSORIES, LOADING, dll.
            $table->string('category', 60);

            // Nama item spesifik — "Lampu Depan", "AC", "Buku Service", dst.
            $table->string('item_name', 120);

            // Hasil inspeksi item ini
            $table->string('result', 10);          // ok | ng

            $table->text('notes')->nullable();

            $table->timestamps();

            // ── Indexes ────────────────────────────────────────────────────────
            $table->index('unit_inspection_id');
            $table->index('result');
            $table->index(['unit_inspection_id', 'result']);   // aggregate NG per inspection
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('unit_inspection_items');
    }
};
