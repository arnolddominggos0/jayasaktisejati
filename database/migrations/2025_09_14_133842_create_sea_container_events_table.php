<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('sea_container_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('container_id')->constrained('sea_containers')->cascadeOnDelete();
            $table->string('event');     // picked_up / gate_in / loaded / arrived / empty_return / dll
            $table->dateTime('event_time');
            $table->string('location')->nullable(); // CY/CT di POL/POD
            $table->string('remark')->nullable();
            $table->timestamps();

            $table->index(['container_id','event_time']);
        });
    }
    public function down(): void { Schema::dropIfExists('sea_container_events'); }
};
