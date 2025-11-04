<?php

namespace App\Models;

use App\Enums\ScheduleState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class ShippingSchedule extends Model
{
    protected $fillable = [
        'voyage_id',
        'shipping_line_id',
        'vessel_id',
        'vessel_name',
        'voyage_no',
        'cargo_plan',
        'jss',
        'dwelling_days',
        'etd',
        'eta',
        'period_month',
        'state',
        'approved_by_name',
        'final_note',
        'final_source',
        'final_attachment_path',
        'finalized_at',
        'kpi_sailing_days',
        'actual_sailing_days',
    ];

    protected $casts = [
        'state' => ScheduleState::class,
        'etd' => 'datetime',
        'eta' => 'datetime',
        'period_month' => 'date',
        'finalized_at' => 'datetime',
    ];

    public function voyage(): BelongsTo
    {
        return $this->belongsTo(Voyage::class);
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function canFinalize(): bool
    {
        $etd = $this->etd ?: $this->voyage?->etd;
        $eta = $this->eta ?: $this->voyage?->eta;
        return $etd && $eta && $eta->gt($etd) && ($this->cargo_plan ?? 0) > 0;
    }

    public function refreshActualSailing(): void
    {
        $atd = $this->voyage?->atd_at;
        $ata = $this->voyage?->ata_at;

        if (is_string($atd)) {
            $atd = Carbon::parse($atd);
        }
        if (is_string($ata)) {
            $ata = Carbon::parse($ata);
        }

        $this->actual_sailing_days = ($atd && $ata && $ata->gt($atd))
            ? $atd->diffInDays($ata)
            : null;

        $this->saveQuietly();
    }
}
