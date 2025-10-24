<?php

namespace App\Models;

use App\Enums\VoyagePlanState;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Builder;

class Voyage extends Model
{
    protected $fillable = [
        'vessel_id',
        'shipping_line_id',
        'voyage_no',
        'port_from_id',
        'port_to_id',
        'service',
    ];

    public function vessel(): BelongsTo
    {
        return $this->belongsTo(Vessel::class);
    }

    public function shippingLine(): BelongsTo
    {
        return $this->belongsTo(ShippingLine::class);
    }

    public function portFrom(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_from_id');
    }

    public function portTo(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'port_to_id');
    }

    public function plans(): HasMany
    {
        return $this->hasMany(VoyagePlan::class);
    }

    public function currentPlan(): ?VoyagePlan
    {
        return $this->plans()
            ->where('state', VoyagePlanState::Final->value)
            ->latest('finalized_at')
            ->latest('id')
            ->first();
    }

    public function hasFinalPlan(): bool
    {
        return $this->plans()->where('state', VoyagePlanState::Final->value)->exists();
    }

    protected function normalizePayload(array $payload): array
    {
        $out = [
            'etd' => $payload['etd'] ?? null,
            'eta' => $payload['eta'] ?? null,
        ];
        return array_filter($out, fn($v) => !is_null($v));
    }

    public function upsertPlan(VoyagePlanState $state, array $payload = [], ?string $notes = null, ?string $source = 'whatsapp', ?int $userId = null): VoyagePlan
    {
        if ($state !== VoyagePlanState::Final) {
            throw new \RuntimeException('Voyage hanya menerima plan final.');
        }

        $payload = $this->normalizePayload($payload);

        return $this->plans()->create([
            'state'        => $state->value,
            'payload'      => $payload,
            'notes'        => $notes,
            'source'       => $source,
            'finalized_at' => now(),
            'created_by'   => $userId,
        ]);
    }

    public function scopeOnlyFinal(Builder $query): Builder
    {
        return $query->whereHas('plans', fn($s) => $s->where('state', 'final'));
    }

    public function getPlanEtdAttribute(): ?Carbon
    {
        $p = $this->currentPlan()?->payload['etd'] ?? null;
        return $p ? Carbon::parse($p) : null;
    }

    public function getPlanEtaAttribute(): ?Carbon
    {
        $p = $this->currentPlan()?->payload['eta'] ?? null;
        return $p ? Carbon::parse($p) : null;
    }

    public function getLabelAttribute(): string
    {
        $line   = $this->shippingLine?->name ?? '-';
        $vessel = $this->vessel?->name ?? '-';
        $voy    = $this->voyage_no ?: '-';
        $pol    = $this->portFrom?->code ?: '-';
        $pod    = $this->portTo?->code ?: '-';

        $etd = $this->plan_etd?->format('d M') ?: '-';
        $eta = $this->plan_eta?->format('d M') ?: '-';

        return "{$line} • {$vessel} • VOY {$voy} • {$pol}→{$pod} • {$etd} → {$eta}";
    }
}
