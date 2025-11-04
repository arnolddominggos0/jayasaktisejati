<?php

namespace App\Models;

use App\Enums\ScheduleState;
use App\Models\Concerns\HasMonthlyOverlap;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class ShippingSchedule extends Model
{
    use HasMonthlyOverlap;
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
        'atd' => 'datetime',
        'ata' => 'datetime',
        'period_month' => 'date',
        'finalized_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $model) {
            if (!is_null($model->actual_sailing_days)) {
                $model->actual_sailing_days = (int) round($model->actual_sailing_days);
            }
            if (!is_null($model->kpi_sailing_days)) {
                $model->kpi_sailing_days = (int) round($model->kpi_sailing_days);
            }
            if (blank($model->jss) || $model->jss === 'AUTO') {
                $model->jss = $model->generateJss();
            }
        });
    }

    public function mount()
    {
        $this->month = request('month', now()->format('Y-m'));
    }

    public function changeMonth($month)
    {
        $this->month = $month;
    }

    public function scopeFinal(Builder $q): Builder
    {
        return $q->where('state', 'final');
    }

    public function scopeOverlapsMonth(Builder $q, int $year, int $month): Builder
    {
        $tz = 'Asia/Jakarta';
        $start = Carbon::createFromDate($year, $month, 1, $tz)->startOfDay();
        $end   = (clone $start)->endOfMonth()->endOfDay();

        return $q->where(function ($qq) use ($start, $end) {
            $qq->whereBetween('etd', [$start, $end])
                ->orWhereBetween('eta', [$start, $end])
                ->orWhere(function ($qqq) use ($start, $end) {
                    $qqq->where('etd', '<=', $start)->where('eta', '>=', $end);
                });
        });
    }

    public function setActualSailingDaysAttribute($value): void
    {
        $this->attributes['actual_sailing_days'] = is_null($value) ? null : (int) round($value);
    }

    public function setKpiSailingDaysAttribute($value): void
    {
        $this->attributes['kpi_sailing_days'] = is_null($value) ? null : (int) round($value);
    }

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
        if (is_string($atd)) $atd = Carbon::parse($atd);
        if (is_string($ata)) $ata = Carbon::parse($ata);
        $this->actual_sailing_days = ($atd && $ata && $ata->gt($atd))
            ? (int) $atd->diffInDays($ata)
            : null;
        $this->saveQuietly();
    }

    public function generateJss(): ?string
    {
        $voyNo = $this->voyage_no ?: $this->voyage?->voyage_no;
        if (!$voyNo) return null;
        $vessel = $this->vessel ?: $this->voyage?->vessel;
        $vesselCode = null;
        if ($vessel && !blank($vessel->code)) {
            $raw = strtoupper(preg_replace('/[^A-Z\-]/', '', $vessel->code));
            $vesselCode = str_contains($raw, '-') ? Str::of($raw)->afterLast('-')->toString() : $raw;
        }
        if (!$vesselCode) {
            $name = strtoupper($vessel?->name ?? $this->vessel_name ?? '');
            $name = trim(str_replace(['K.M.', 'K.M', 'KM', '  '], ' ', $name));
            $parts = array_values(array_filter(explode(' ', $name)));
            if (count($parts) >= 2) {
                $first = $parts[0];
                $last  = $parts[count($parts) - 1];
                $vesselCode = substr($first, 0, 1) . substr($last, 0, 2);
            } elseif (count($parts) === 1) {
                $vesselCode = substr($parts[0], 0, 3);
            } else {
                $vesselCode = 'VSL';
            }
        }
        $podCode = strtoupper($this->voyage?->pod?->code ?? '');
        return 'VOY' . $voyNo . $vesselCode . $podCode . 'JSS';
    }
}
