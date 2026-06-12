<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('container_readiness_sessions', function (Blueprint $table) {
            $table->id();

            // Satu baris per hari — demand planning harian
            $table->date('session_date')->unique();

            // Jumlah unit / SPPB hari itu
            $table->unsignedSmallInteger('unit_count')->default(0)
                ->comment('Jumlah unit / dokumen SPPB hari ini');

            // Kebutuhan container berdasarkan unit_count
            $table->unsignedSmallInteger('container_need')->default(0)
                ->comment('Jumlah container yang dibutuhkan');

            // Container yang tersedia (fisik di yard / dikonfirmasi)
            $table->unsignedSmallInteger('container_available')->default(0)
                ->comment('Jumlah container yang tersedia');

            // Computed & stored — available - need
            $table->smallInteger('gap')->default(0)
                ->comment('available - need; positif = surplus, negatif = kurang');

            // Computed & stored — gap >= 0
            $table->boolean('summary_sufficient')->default(false)
                ->comment('true jika container_available >= container_need');

            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('container_readiness_sessions');
    }
};
