<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('company', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 50)->nullable();
            $table->text('message');
            $table->foreignId('vessel_listing_id')
                ->nullable()
                ->constrained('jsl_vessel_listings')
                ->nullOnDelete();
            $table->boolean('consent_given')->default(false);
            $table->string('status', 20)->default('new');
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->index('status');
            $table->index('vessel_listing_id');
            $table->index('created_at');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_inquiries');
    }
};
