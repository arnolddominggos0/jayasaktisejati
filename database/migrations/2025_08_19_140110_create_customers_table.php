<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();

            $table->string('code', 20)->unique();          
            $table->string('name', 191);

            $table->string('email', 191)->nullable()->unique();

            $table->string('nik', 16)->nullable()->unique();

            $table->string('npwp', 32)->nullable();

            $table->string('pic_name', 100)->nullable();
            $table->string('pic_phone', 191)->nullable();

            $table->string('phone_number', 32)->nullable();

            $table->foreignId('city_id')->nullable()->nullOnDelete();
            $table->text('address')->nullable();
            $table->string('postal_code', 10)->nullable();
            $table->timestampsTz();

            $table->index(['name']);
            $table->index(['city_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
