<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('running_numbers', function (Blueprint $table) {
            $table->id();
            $table->string('series')->index();
            $table->unsignedBigInteger('value')->default(0);
            $table->timestamps();

            $table->unique('series');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('running_numbers');
    }
};
