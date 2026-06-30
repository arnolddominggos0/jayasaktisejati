<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JslCompanyProfile extends Model
{
    protected $table = 'jsl_company_profiles';

    protected $fillable = [
        'about',
        'overview',
        'vision',
        'mission',
        'about_en',
        'overview_en',
        'vision_en',
        'mission_en',
    ];

    public static function current(): self
    {
        return static::first() ?? static::create();
    }
}
