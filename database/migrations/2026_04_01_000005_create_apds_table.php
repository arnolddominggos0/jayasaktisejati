<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('apds', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('nama');
            $table->enum('helm', ['Layak', 'Perlu Ganti'])->default('Layak');
            $table->enum('sepatu_safety', ['Layak', 'Perlu Ganti'])->default('Layak');
            $table->enum('rompi', ['Layak', 'Perlu Ganti'])->default('Layak');
            $table->enum('sarung_tangan', ['Layak', 'Perlu Ganti'])->default('Layak');
            $table->enum('status_keseluruhan', ['Layak Pakai', 'Perlu Perhatian'])->default('Layak Pakai');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('apds');
    }
};
