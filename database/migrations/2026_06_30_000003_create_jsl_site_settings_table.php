<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jsl_site_settings', function (Blueprint $table) {
            $table->id();
            $table->string('site_name');
            $table->string('tagline')->nullable();
            $table->text('footer_text')->nullable();
            $table->string('site_name_en')->nullable();
            $table->string('tagline_en')->nullable();
            $table->text('footer_text_en')->nullable();
            $table->text('contact_address')->nullable();
            $table->string('contact_phone_display', 50)->nullable();
            $table->string('contact_email_display', 255)->nullable();
            $table->string('social_facebook_url', 500)->nullable();
            $table->string('social_instagram_url', 500)->nullable();
            $table->string('social_linkedin_url', 500)->nullable();
            $table->string('broker_whatsapp', 50)->nullable();
            $table->string('broker_email', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jsl_site_settings');
    }
};
