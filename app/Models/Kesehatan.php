<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kesehatan extends Model
{
    use HasFactory;

    protected $table = 'kesehatans';

    protected $fillable = [
        'tanggal',
        'nama',
        'suhu',
        'sistole',
        'diastole',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'suhu' => 'decimal:1',
    ];
}
