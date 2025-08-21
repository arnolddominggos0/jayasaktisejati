<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'name',
        'nik',
        'npwp',
        'phone_number',
        'email',
        'office_id'
    ];

    public function office()
    {
        return $this->belongsTo(Office::class);
    }
}
