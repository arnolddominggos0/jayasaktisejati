<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::dropIfExists('depo_activities');
    }
    public function down(): void {
        Schema::create('depo_activities', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal');
            $table->foreignId('depot_id')->constrained();
            $table->string('metric');
            $table->integer('nilai')->default(0);
            $table->timestamps();
        });
    }
};
