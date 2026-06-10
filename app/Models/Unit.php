<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Unit extends Model
{
    use HasFactory;

    protected $table = 'units';

    protected $fillable = [
        'shipment_id',
        'model_no',
        'reg_no',
        'chassis_no',
        'engine_no',
        'color',
        'do_number',
        'qty',
        'container_display',
        'notes',
    ];

    protected $casts = [
        'qty' => 'integer',
    ];

    public function shipment()
    {
        return $this->belongsTo(Shipment::class, 'shipment_id', 'id');
    }

    public function unitChecks(): HasMany
    {
        return $this->hasMany(UnitCheck::class);
    }

    /**
     * Seluruh inspection history unit ini, urut sesuai urutan stage journey.
     * Order: pickup → handover_depot → loading → unloading → selfdrive → dooring
     */
    public function inspections(): HasMany
    {
        return $this->hasMany(UnitInspection::class)
            ->orderByRaw(
                "ARRAY_POSITION(ARRAY['pickup','handover_depot','loading','unloading','selfdrive','dooring']::text[], stage)"
            );
    }
}
