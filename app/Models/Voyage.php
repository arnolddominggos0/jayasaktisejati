<?php

namespace App\Models;

use App\Enums\ScheduleState;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class Voyage extends Model
{
    use HasFactory;

    protected $fillable = [
        'vessel_id',
        'pol_id',
        'pod_id',
        'voyage_no',
        'etd',
        'eta',
        'atd_at',
        'ata_at',
        'period_month',
        'cargo_plan',
        'cargo_actual',
        'cargo_actual_reported_at',
        'cargo_actual_reported_by',
        'dwelling_days',
        'kpi_sailing_days',
        'actual_sailing_days',
        'is_delayed',
        'delay_reason',
        'delay_reported_at',
        'rescheduled_etd',
        'rescheduled_eta',
        'is_final',
        'finalized_at',
        'finalized_by',
        'finalized_by_name',
        'jss',
        'approved_by_name',
        'final_note',
        'final_source',
        'final_attachment_path',
        'is_urgent',
    ];

    protected $attributes = [
        'dwelling_days' => 6,
        'kpi_sailing_days' => 11,
        'is_final' => false,
    ];

    protected $casts = [
        'etd'                        => 'datetime',
        'eta'                        => 'datetime',
        'atd_at'                     => 'datetime',
        'ata_at'                     => 'datetime',
        'period_month'               => 'date',
        'finalized_at'               => 'datetime',
        'delay_reported_at'          => 'datetime',
        'cargo_actual_reported_at'   => 'datetime',
        'rescheduled_etd'            => 'datetime',
        'rescheduled_eta'            => 'datetime',
        'is_delayed'                 => 'boolean',
        'is_final'                   => 'boolean',
        'cargo_actual'               => 'integer',
        'actual_sailing_days'        => 'decimal:2',
        'finalized_by'               => 'integer',
        'is_urgent'                  => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if ($model->etd && ! $model->period_month) {
                $model->period_month = $model->etd->copy()->startOfMonth();
            }
            if (! $model->jss && $model->vessel && $model->pod) {
                $voyageNo = preg_replace('/\s+/', '', strtoupper((string) $model->voyage_no));
                $vcode = strtoupper(preg_replace('/[^A-Z0-9]/', '', substr((string) ($model->vessel->name ?? 'VSL'), 0, 3)));
                $podCode = strtoupper(preg_replace('/[^A-Z0-9]/', '', (string) ($model->pod->code ?? 'POD')));
                if ($voyageNo && $vcode && $podCode) {
                    $model->jss = 'VOY' . $voyageNo . $vcode . $podCode . 'JSS';
                }
            }
            $model->is_final = true;
            $model->finalized_at = $model->finalized_at ?? now();
            if (Auth::check()) {
                $model->finalized_by = $model->finalized_by ?? Auth::id();
                $model->finalized_by_name = $model->finalized_by_name ?? Auth::user()->name;
            }
        });

        static::saving(function (self $model) {
            if ($model->atd_at && $model->ata_at) {
                $seconds = $model->atd_at->diffInSeconds($model->ata_at);
                $daysDecimal = $seconds / 86400;
                $model->actual_sailing_days = max(0, round($daysDecimal, 2));
            }
            if ($model->is_delayed && $model->delay_reason && ! $model->delay_reported_at) {
                $model->delay_reported_at = now();
            }
            if ($model->rescheduled_etd) {
                $model->etd = $model->rescheduled_etd;
            }
            if ($model->rescheduled_eta) {
                $model->eta = $model->rescheduled_eta;
            }
            if ($model->is_final && ! $model->finalized_at) {
                $model->finalized_at = now();
                if (Auth::check()) {
                    $model->finalized_by = Auth::id();
                    $model->finalized_by_name = Auth::user()->name;
                }
            }
            if ($model->isDirty('cargo_actual') && $model->cargo_actual && ! $model->cargo_actual_reported_at) {
                $model->cargo_actual_reported_at = now();
                if (Auth::check()) {
                    $model->cargo_actual_reported_by = Auth::user()->name;
                }
            }
        });

        static::saved(function (self $model) {
            $model->syncSchedule();
            $originalEtd = $model->getOriginal('etd');
            $originalEta = $model->getOriginal('eta');
            $changedEtd = $originalEtd != $model->etd;
            $changedEta = $originalEta != $model->eta;
            if ($changedEtd || $changedEta) {
                $oldEtd = $originalEtd ? \Illuminate\Support\Carbon::parse($originalEtd) : null;
                $oldEta = $originalEta ? \Illuminate\Support\Carbon::parse($originalEta) : null;
                $newEtd = $model->etd;
                $newEta = $model->eta;
                \App\Models\RescheduleLog::create([
                    'voyage_id' => $model->id,
                    'old_etd' => $oldEtd,
                    'new_etd' => $newEtd,
                    'old_eta' => $oldEta,
                    'new_eta' => $newEta,
                    'reason' => $model->delay_reason ?? null,
                    'changed_by' => Auth::check() ? Auth::id() : null,
                    'changed_by_name' => Auth::check() ? Auth::user()->name : null,
                ]);
            }
        });
    }

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class, 'vessel_id');
    }

    public function pol(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(ShippingSchedule::class, 'voyage_id');
    }

    public function rescheduleLogs(): HasMany
    {
        return $this->hasMany(RescheduleLog::class, 'voyage_id');
    }

    public function getCargoGapAttribute(): ?int
    {
        if (is_null($this->cargo_plan) || is_null($this->cargo_actual)) {
            return null;
        }
        return $this->cargo_actual - $this->cargo_plan;
    }

    public function getCargoAchievementPercentAttribute(): ?float
    {
        if (is_null($this->cargo_plan) || $this->cargo_plan == 0 || is_null($this->cargo_actual)) {
            return null;
        }
        return round(($this->cargo_actual / $this->cargo_plan) * 100, 2);
    }

    public function syncSchedule(): void
    {
        if (! $this->id) {
            return;
        }
        $voyage = $this;
        DB::transaction(function () use ($voyage) {
            try {
                if (! Schema::hasTable('shipping_schedules') || ! Schema::hasColumn('shipping_schedules', 'voyage_id')) {
                    Log::error('syncSchedule aborted: shipping_schedules table or voyage_id column missing');
                    return;
                }
                $schedule = $voyage->schedule()->firstOrNew(['voyage_id' => $voyage->id]);
                $payload = [];
                $setIfColumnExists = function ($col, $value) use (&$payload) {
                    if (! Schema::hasColumn('shipping_schedules', $col)) {
                        return;
                    }
                    if ($value === '') {
                        $value = null;
                    }
                    if (! is_null($value)) {
                        $payload[$col] = $value;
                    }
                };
                $payload['voyage_id'] = $voyage->id;
                $setIfColumnExists('shipping_line_id', $voyage->vessel?->shippingLine?->id ?? $schedule->shipping_line_id ?? null);
                $setIfColumnExists('vessel_id', $voyage->vessel_id ?? $schedule->vessel_id ?? null);
                $setIfColumnExists('pol_id', $voyage->pol_id ?? $schedule->pol_id ?? null);
                $setIfColumnExists('pod_id', $voyage->pod_id ?? $schedule->pod_id ?? null);
                $setIfColumnExists('voyage_no', $voyage->voyage_no ?? $schedule->voyage_no ?? null);
                $setIfColumnExists('cargo_plan', $voyage->cargo_plan ?? $schedule->cargo_plan ?? null);
                $setIfColumnExists('cargo_actual', $voyage->cargo_actual ?? $schedule->cargo_actual ?? null);
                $setIfColumnExists('cargo_actual_reported_at', $voyage->cargo_actual_reported_at ?? $schedule->cargo_actual_reported_at ?? null);
                $setIfColumnExists('cargo_actual_reported_by', $voyage->cargo_actual_reported_by ?? $schedule->cargo_actual_reported_by ?? null);
                $setIfColumnExists('jss', $voyage->jss ?? $schedule->jss ?? null);
                $setIfColumnExists('dwelling_days', $voyage->dwelling_days ?? $schedule->dwelling_days ?? null);
                $setIfColumnExists('kpi_sailing_days', $voyage->kpi_sailing_days ?? $schedule->kpi_sailing_days ?? null);
                $setIfColumnExists('actual_sailing_days', $voyage->actual_sailing_days ?? $schedule->actual_sailing_days ?? null);
                $setIfColumnExists('etd', $voyage->etd ?? $schedule->etd ?? null);
                $setIfColumnExists('eta', $voyage->eta ?? $schedule->eta ?? null);
                $setIfColumnExists('period_month', $voyage->period_month ?? $schedule->period_month ?? null);
                $setIfColumnExists('approved_by_name', $voyage->approved_by_name ?? $schedule->approved_by_name ?? null);
                $setIfColumnExists('final_note', $voyage->final_note ?? $schedule->final_note ?? null);
                $setIfColumnExists('final_source', $voyage->final_source ?? $schedule->final_source ?? null);
                $setIfColumnExists('final_attachment_path', $voyage->final_attachment_path ?? $schedule->final_attachment_path ?? null);
                $setIfColumnExists('vessel_name', $voyage->vessel?->name ?? $schedule->vessel_name ?? null);
                $setIfColumnExists('finalized_by', $voyage->finalized_by ?? $schedule->finalized_by ?? null);
                $setIfColumnExists('finalized_by_name', $voyage->finalized_by_name ?? $schedule->finalized_by_name ?? null);
                $setIfColumnExists('finalized_at', $voyage->finalized_at ?? $schedule->finalized_at ?? null);
                $setIfColumnExists('is_urgent', $voyage->is_urgent ?? $schedule->is_urgent ?? null);

                if (Schema::hasColumn('shipping_schedules', 'state')) {
                    $payload['state'] = \App\Enums\ScheduleState::Final->value;
                }

                if (isset($payload['state']) && $payload['state'] === \App\Enums\ScheduleState::Final->value) {
                    if (Schema::hasColumn('shipping_schedules', 'finalized_at') && empty($payload['finalized_at'])) {
                        $payload['finalized_at'] = $voyage->finalized_at ?? now();
                    }
                    if (Schema::hasColumn('shipping_schedules', 'finalized_by') && empty($payload['finalized_by'])) {
                        $payload['finalized_by'] = $voyage->finalized_by ?? (Auth::check() ? Auth::id() : null);
                    }
                    if (Schema::hasColumn('shipping_schedules', 'finalized_by_name') && empty($payload['finalized_by_name'])) {
                        $payload['finalized_by_name'] = $voyage->finalized_by_name ?? (Auth::check() ? Auth::user()->name : null);
                    }
                }

                $schedule->fill($payload);

                if ($schedule->isDirty()) {
                    $schedule->save();
                } elseif (! $schedule->exists) {
                    \DB::table('shipping_schedules')->updateOrInsert(
                        ['voyage_id' => $voyage->id],
                        $payload
                    );
                    $schedule = $voyage->schedule()->first();
                }

                if (Schema::hasColumn('shipping_schedules', 'state') && ($schedule->getRawOriginal('state') ?? ($payload['state'] ?? null)) === \App\Enums\ScheduleState::Final->value) {
                    if (! $voyage->is_final) {
                        $voyage->is_final = true;
                        $voyage->finalized_at = $voyage->finalized_at ?? $schedule->finalized_at ?? now();
                        $voyage->finalized_by = $voyage->finalized_by ?? $schedule->finalized_by;
                        $voyage->finalized_by_name = $voyage->finalized_by_name ?? $schedule->finalized_by_name;
                        if (method_exists($voyage, 'saveQuietly')) {
                            $voyage->saveQuietly();
                        } else {
                            $voyage->save();
                        }
                    }
                }
            } catch (\Throwable $e) {
                Log::error('syncSchedule exception: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                throw $e;
            }
        });
    }
}
