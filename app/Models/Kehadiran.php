<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kehadiran extends Model
{
    use HasFactory;

    protected $table = 'kehadirans';

    protected $fillable = [
        'tanggal',
        'nama',
        'status',
        'jam_masuk',
        'jam_keluar',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
