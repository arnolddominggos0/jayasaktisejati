<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'code',     
        'name',
        'email',
        'phone_number',
        'nik',
        'npwp',
        'office_id' 
    ];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }
}
