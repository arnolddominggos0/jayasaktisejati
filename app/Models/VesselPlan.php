<?php

namespace App\Models;

use App\Enums\VesselPlanStatus;
use App\Services\VesselPlanAnalyzer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use DomainException;

class VesselPlan extends Model
{
    protected $fillable = [
        'period_month',
        'pol_id',
        'pod_id',
        'route_code',
        'status',
        'note',
        'sent_at',
        'sent_by',
    ];

    protected $casts = [
        'status'       => VesselPlanStatus::class,
        'period_month' => 'date',
        'sent_at'      => 'datetime',
    ];


    public function pol(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pol_id');
    }

    public function pod(): BelongsTo
    {
        return $this->belongsTo(Port::class, 'pod_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(VesselPlanItem::class);
    }

    public function voyages(): HasMany
    {
        return $this->hasMany(Voyage::class);
    }

    public function isDraft(): bool
    {
        return $this->status === VesselPlanStatus::Draft;
    }

    public function isSent(): bool
    {
        return $this->status === VesselPlanStatus::Sent;
    }

    public function isFinal(): bool
    {
        return $this->status === VesselPlanStatus::Final;
    }

    public function markAsSent(int $userId): void
    {
        if (! $this->isDraft()) {
            throw new DomainException('Hanya draft yang bisa dikirim ke TAM.');
        }

        $this->update([
            'status'  => VesselPlanStatus::Sent,
            'sent_at' => now(),
            'sent_by' => $userId,
        ]);
    }


    public function analyze(): array
    {
        return app(VesselPlanAnalyzer::class)->analyze($this);
    }

    public function etdGaps(): array
    {
        return $this->analyze()['gaps'] ?? [];
    }

    public function maxEtdGap(): int
    {
        return (int) ($this->analyze()['max_gap'] ?? 0);
    }

    public function generateVoyages(int $userId): int
    {
        if (! $this->isSent()) {
            throw new DomainException('Vessel plan harus status Review TAM.');
        }

        if (! $this->pol_id || ! $this->pod_id) {
            throw new DomainException('POL dan POD wajib diisi.');
        }

        if ($this->voyages()->exists()) {
            throw new DomainException('Voyage dari plan ini sudah pernah dibuat.');
        }

        return DB::transaction(function () use ($userId) {
            $count = 0;

            foreach ($this->items()->with('vessel')->orderBy('planned_etd')->get() as $index => $item) {

                Voyage::create([
                    'vessel_plan_id' => $this->id,
                    'vessel_id'      => $item->vessel_id,
                    'pol_id'         => $this->pol_id,
                    'pod_id'         => $this->pod_id,

                    'voyage_no' => sprintf(
                        '%s-%s-%02d',
                        strtoupper(substr($item->vessel?->name ?? 'VSL', 0, 3)),
                        $this->period_month->format('ym'),
                        $index + 1
                    ),

                    'etd'          => $item->planned_etd,
                    'eta'          => $item->planned_eta,
                    'period_month' => $this->period_month,

                    'is_final'     => true,
                    'finalized_at' => now(),
                    'finalized_by' => $userId,
                    'finalized_by_name' => auth_user()?->name,
                ]);

                $count++;
            }

            $this->update([
                'status' => VesselPlanStatus::Final,
            ]);

            return $count;
        });
    }

    public function waUrl(): string
    {
        $phone = config('services.tam.whatsapp');
        $text  = urlencode($this->waMessage());

        return "https://wa.me/{$phone}?text={$text}";
    }

    public function waMessage(): string
    {
        $analysis = $this->analyze();

        $lines = [];
        $lines[] = $this->waGreeting() . ' Pak,';
        $lines[] = '';
        $lines[] = "Berikut draft jadwal kapal periode {$this->period_month->format('M Y')} rute {$this->route_code}:";
        $lines[] = '';

        foreach ($this->items()->with(['shippingLine', 'vessel'])->orderBy('planned_etd')->get() as $i => $item) {
            $lines[] = ($i + 1) . '. ' . ($item->shippingLine->name ?? '-');
            $lines[] = 'Vessel : ' . ($item->vessel->name ?? '-');
            $lines[] = 'ETD    : ' . $item->planned_etd->format('d M Y');
            $lines[] = 'ETA    : ' . $item->planned_eta->format('d M Y');
            $lines[] = '';
        }

        $lines[] = 'Analisa SOP:';
        $lines[] = '- Max ETD Gap: ' . ($analysis['max_gap'] ?? 0) . ' hari';
        $lines[] = '- Status: ' . (($analysis['ok'] ?? false) ? 'SESUAI SOP' : 'MELANGGAR SOP');
        $lines[] = '';
        $lines[] = 'Mohon konfirmasi / revisinya, ya Pak.';
        $lines[] = 'Terima kasih.';

        return implode("\n", $lines);
    }

    protected function waGreeting(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour >= 4 && $hour < 11  => 'Selamat pagi',
            $hour >= 11 && $hour < 15 => 'Selamat siang',
            $hour >= 15 && $hour < 18 => 'Selamat sore',
            default                  => 'Selamat malam',
        };
    }

    public function setPeriodMonthAttribute($value): void
    {
        $this->attributes['period_month'] =
            Carbon::parse($value)->startOfMonth()->toDateString();
    }

    protected static function booted(): void
    {
        static::saving(function (VesselPlan $plan) {
            if ($plan->pol && $plan->pod) {
                $plan->route_code = "{$plan->pol->code}-{$plan->pod->code}";
            }
        });
    }
}
