<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Apd extends Model
{
    use HasFactory;

    protected $table = 'apds';

    protected $fillable = [
        'tanggal',
        'nama',
        'helm',
        'sepatu_safety',
        'rompi',
        'sarung_tangan',
        'status_keseluruhan',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
