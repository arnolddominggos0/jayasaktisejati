<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_vessel_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('vessel_listing_id')
                ->constrained('jsl_vessel_listings')
                ->cascadeOnDelete();
            $table->foreignId('media_asset_id')
                ->constrained('jsl_media_assets')
                ->restrictOnDelete();
            $table->integer('sort_order')->default(0);
            $table->string('alt_text', 255)->nullable();
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->index('vessel_listing_id');
            $table->index(['vessel_listing_id', 'sort_order']);
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_vessel_images');
    }
};
