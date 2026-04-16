<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('briefings', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->string('pic');
            $table->string('topik');
            $table->integer('peserta');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('briefings');
    }
};
