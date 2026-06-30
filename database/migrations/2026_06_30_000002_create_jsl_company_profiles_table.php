<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_company_profiles', function (Blueprint $table) {
            $table->id();
            $table->longText('about')->nullable();
            $table->longText('overview')->nullable();
            $table->text('vision')->nullable();
            $table->text('mission')->nullable();
            $table->longText('about_en')->nullable();
            $table->longText('overview_en')->nullable();
            $table->text('vision_en')->nullable();
            $table->text('mission_en')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_company_profiles');
    }
};
