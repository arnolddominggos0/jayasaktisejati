<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('fleet_schedules', function (Blueprint $t) {
            $t->id();
            $t->string('vessel_name');
            $t->string('voyage')->nullable();
            $t->string('pol')->nullable();
            $t->string('pod')->nullable();
            $t->timestampTz('etd')->nullable();
            $t->timestampTz('eta')->nullable();
            $t->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fleet_schedules');
    }
};
