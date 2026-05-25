<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockApdCheck extends Model
{
    protected $fillable = [
        'session_id',
        'ppe_type',
        'stock_available',
        'required_quantity',
        'remark',
    ];

    public function session(): BelongsTo
    {
        return $this->belongsTo(BriefingSession::class, 'session_id');
    }
}
