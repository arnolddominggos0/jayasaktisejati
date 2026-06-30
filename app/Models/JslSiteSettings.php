<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JslSiteSettings extends Model
{
    protected $table = 'jsl_site_settings';

    protected $fillable = [
        'site_name',
        'tagline',
        'footer_text',
        'site_name_en',
        'tagline_en',
        'footer_text_en',
        'contact_address',
        'contact_phone_display',
        'contact_email_display',
        'social_facebook_url',
        'social_instagram_url',
        'social_linkedin_url',
        'broker_whatsapp',
        'broker_email',
    ];

    public static function current(): self
    {
        return static::first() ?? static::create([
            'site_name' => 'Jaya Sakti Line',
        ]);
    }
}
