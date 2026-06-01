<?php

namespace App\Models;

use App\Enums\ArmadaStatus;
use App\Enums\ArmadaType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Armada extends Model
{
    protected $fillable = [
        'code',
        'type',
        'plate_number',
        'capacity',
        'branch_id',
        'depot_id',
        'notes'
    ];

    protected $casts = [
        'type'   => ArmadaType::class,
        'status' => ArmadaStatus::class,
    ];

    public function getDisplayNameAttribute(): string
    {
        $code  = $this->code ?? '–';
        $plate = $this->plate_number ?? '–';
        return trim($code . ' - ' . $plate);
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ArmadaStatusLog::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function maintenances(): HasMany
    {
        return $this->hasMany(ArmadaMaintenance::class);
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ArmadaAssignment::class);
    }

    public static function booted(): void
    {
        static::creating(function (Armada $armada) {
            if (! $armada->status) {
                $armada->status = ArmadaStatus::Available;
            }
        });

        static::updating(function (Armada $armada) {
            if ($armada->isDirty('status') && ! app()->bound('armada.transitioning')) {
                throw new \RuntimeException('Status hanya bisa diubah melalui aksi transisi.');
            }
        });
    }

    public function setStatusAttribute($value): void
    {
        if ($value instanceof \App\Enums\ArmadaStatus) {
            $this->attributes['status'] = $value->value;
        } else {
            $this->attributes['status'] = strtolower((string) $value);
        }
    }


    public static function resolvePrefixFromTypeValue(?string $typevalue): string
    {
        if (! $typevalue) return 'AR';
        $value   = strtolower($typevalue);
        $compact = preg_replace('/[\s\-_]+/', '', $value);

        if ($value === 'cc' || preg_match('/\bcc\b/', $value) || str_contains($compact, 'carcarrier')) return 'CC';
        if ($value === 'tw' || preg_match('/\btw\b/', $value) || str_contains($compact, 'towing') || str_contains($compact, 'tow')) return 'TW';
        if ($value === 'truck' || str_contains($compact, 'truck')) return 'TR';

        $letters = preg_replace('/[^a-z]/', '', $value) ?: 'ar';
        return strtoupper(substr($letters, 0, 2));
    }


    public static function nextCodeForPrefix(string $prefix, int $pad = 3): string
    {
        return DB::transaction(function () use ($prefix, $pad) {
            $row = DB::table('armada_code_counters')
                ->where('prefix', $prefix)
                ->lockForUpdate()
                ->first();

            $next = ($row?->last_number ?? 0) + 1;

            DB::table('armada_code_counters')->updateOrInsert(
                ['prefix' => $prefix],
                ['last_number' => $next, 'updated_at' => now(), 'created_at' => $row?->created_at ?? now()]
            );
            return sprintf('%s%0' . $pad . 'd', $prefix, $next);
        });
    }

    public static function previewNextCode(string $prefix, int $pad = 3): string
    {
        $last = DB::table('armada_code_counters')->where('prefix', $prefix)->value('last_number') ?? 0;
        return sprintf('%s%0' . $pad . 'd', $prefix, $last + 1);
    }


    public function transitionTo(ArmadaStatus $to, ?string $reason = null): void
    {
        DB::transaction(function () use ($to, $reason) {
            $from = $this->status?->value;

            if ($this->status === ArmadaStatus::Maintenance && $to === ArmadaStatus::OnDuty) {
                throw new \RuntimeException('Armada masih Maintenance. Selesaikan dulu');
            }

            app()->instance('armada.transitioning', true);

            $this->forceFill(['status' => $to])->save();

            $this->statusLogs()->create([
                'from_status' => $from,
                'to_status'   => $to->value,
                'reason'      => $reason,
                'changed_by'  => Auth::id(),
                'changed_at'  => now(),
            ]);
        });
    }

    public function scopeAssignable(Builder $query, ?string $date = null, ?int $branchId = null): Builder
    {
        return $query->when($branchId, fn($qq) => $qq->where('branch_id', $branchId))
            ->where('status', ArmadaStatus::Available)
            ->when($date, function ($qq) use ($date) {
                $qq->whereDoesntHave('assignments', fn($aq) => $aq->whereDate('date', $date));
            });
    }
}
