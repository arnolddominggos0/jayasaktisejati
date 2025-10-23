<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sea_containers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();           
            $table->string('size', 10)->nullable();    
            $table->string('type', 20)->nullable();     
            $table->string('status', 20)->nullable();   
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sea_containers');
    }
};
