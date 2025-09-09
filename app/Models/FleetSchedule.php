<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FleetSchedule extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'vessel_name', 'voyage', 'pol', 'pod', 'etd', 'eta',
    ];

    protected $casts = [
        'etd' => 'datetime',
        'eta' => 'datetime',
    ];

    public function getDisplayLabelAttribute(): string
    {
        return sprintf('%s / %s — %s (%s → %s)',
            $this->vessel_name,
            $this->voyage ?: '-',
            optional($this->etd)->format('d M Y') ?: '-',
            $this->pol ?: '-',
            $this->pod ?: '-',
        );
    }
}
