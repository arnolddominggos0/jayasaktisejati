<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('depot_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depot_id')->constrained('depots')->cascadeOnDelete();
            $table->date('date');
            $table->string('metric');  
            $table->integer('value')->default(0);
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->unique(['depot_id','date','metric']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depot_activities');
    }
};
