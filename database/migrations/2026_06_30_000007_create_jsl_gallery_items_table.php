<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_gallery_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('media_asset_id')
                ->constrained('jsl_media_assets')
                ->restrictOnDelete();
            $table->string('caption', 255)->nullable();
            $table->string('category', 100)->nullable();
            $table->string('caption_en', 255)->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->timestamp('deleted_at')->nullable();

            $table->index('sort_order');
            $table->index('category');
            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_gallery_items');
    }
};
