<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loadings', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('no_do');
            $table->string('customer');
            $table->string('material');
            $table->integer('jumlah');
            $table->string('satuan');
            $table->enum('status', ['Selesai', 'Proses', 'Pending'])->default('Proses');
            $table->string('pic');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loadings');
    }
};
