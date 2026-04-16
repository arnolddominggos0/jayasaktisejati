<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Briefing extends Model
{
    use HasFactory;

    protected $fillable = [
        'tanggal',
        'pic',
        'topik',
        'peserta',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
