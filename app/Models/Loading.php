<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loading extends Model
{
    use HasFactory;

    protected $table = 'loadings';

    protected $fillable = [
        'tanggal',
        'no_do',
        'customer',
        'material',
        'jumlah',
        'satuan',
        'status',
        'pic',
        'keterangan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}
