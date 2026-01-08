<?php

namespace App\Models;

use App\Enums\VesselPlanStatus;
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
        'period_month' => 'date',
        'status'       => VesselPlanStatus::class,
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


    public function etdGaps(): array
    {
        $items = $this->items()
            ->orderBy('planned_etd')
            ->get();

        $gaps = [];

        foreach ($items as $i => $item) {
            $next = $items[$i + 1] ?? null;

            $gaps[$item->id] = $next
                ? $item->planned_etd->diffInDays($next->planned_etd)
                : null;
        }

        return $gaps;
    }

    public function analyze(): array
    {
        $gaps = $this->etdGaps();

        $numeric = array_filter($gaps, fn ($v) => is_int($v));
        $maxGap  = empty($numeric) ? 0 : max($numeric);

        return [
            'gaps'     => $gaps,
            'max_gap' => $maxGap,
            'ok'      => $maxGap <= 6,
        ];
    }

    public function maxEtdGap(): int
    {
        return (int) $this->analyze()['max_gap'];
    }

    public function sopStatus(): array
    {
        if (! $this->items()->exists()) {
            return [
                'label' => 'BELUM ADA JADWAL',
                'color' => 'gray',
                'ok'    => false,
            ];
        }

        $analysis = $this->analyze();

        return $analysis['ok']
            ? [
                'label' => 'SESUAI SOP',
                'color' => 'success',
                'ok'    => true,
            ]
            : [
                'label' => 'MELANGGAR SOP',
                'color' => 'danger',
                'ok'    => false,
            ];
    }

    public function canSendToTam(): bool
    {
        return $this->isDraft()
            && $this->items()->exists()
            && $this->sopStatus()['ok'] === true;
    }

    public function markAsSent(int $userId): void
    {
        if (! $this->canSendToTam()) {
            throw new DomainException('Jadwal belum siap dikirim ke TAM.');
        }

        $this->update([
            'status'  => VesselPlanStatus::Sent,
            'sent_at' => now(),
            'sent_by' => $userId,
        ]);
    }

    public function generateVoyages(int $userId): int
    {
        if (! $this->isSent()) {
            throw new DomainException('Vessel Plan harus status SENT.');
        }

        $this->resolveRoute();

        if ($this->voyages()->exists()) {
            throw new DomainException('Voyage sudah pernah dibuat.');
        }

        return DB::transaction(function () {
            $count = 0;

            foreach ($this->items()->orderBy('planned_etd')->get() as $i => $item) {

                Voyage::create([
                    'vessel_plan_id' => $this->id,
                    'vessel_id'      => $item->vessel_id,
                    'pol_id'         => $this->pol_id,
                    'pod_id'         => $this->pod_id,
                    'voyage_no'      => sprintf(
                        'TAM-%s-%02d',
                        $this->period_month->format('ym'),
                        $i + 1
                    ),
                    'etd'          => $item->planned_etd,
                    'eta'          => $item->planned_eta,
                    'period_month' => $this->period_month,
                ]);

                $count++;
            }

            $this->update([
                'status' => VesselPlanStatus::Final,
            ]);

            return $count;
        });
    }

    protected function resolveRoute(): void
    {
        if ($this->pol_id && $this->pod_id) {
            return;
        }

        $polCode = config('tam.route.pol_code');
        $podCode = config('tam.route.pod_code');

        if (! $polCode || ! $podCode) {
            throw new DomainException('Konfigurasi POL / POD TAM belum diatur.');
        }

        $pol = Port::where('code', $polCode)->first();
        $pod = Port::where('code', $podCode)->first();

        if (! $pol || ! $pod) {
            throw new DomainException(
                "Port TAM tidak ditemukan (POL={$polCode}, POD={$podCode})"
            );
        }

        $this->updateQuietly([
            'pol_id'     => $pol->id,
            'pod_id'     => $pod->id,
            'route_code' => "{$pol->code}-{$pod->code}",
        ]);
    }

    /* ==============================
     | WHATSAPP
     ============================== */

    public function waUrl(): string
    {
        $phone = config('services.tam.whatsapp');

        return 'https://wa.me/' . $phone . '?text=' . urlencode($this->waMessage());
    }

    public function waMessage(): string
    {
        $analysis = $this->analyze();

        $lines = [
            $this->waGreeting() . ' Pak,',
            '',
            "Berikut draft jadwal kapal periode {$this->period_month->format('M Y')} rute {$this->route_code}:",
            '',
        ];

        foreach ($this->items()->with(['shippingLine', 'vessel'])->orderBy('planned_etd')->get() as $i => $item) {
            $lines[] = ($i + 1) . '. ' . ($item->shippingLine->name ?? '-');
            $lines[] = 'Kapal : ' . ($item->vessel->name ?? '-');
            $lines[] = 'ETD   : ' . $item->planned_etd->format('d M Y');
            $lines[] = 'ETA   : ' . $item->planned_eta->format('d M Y');
            $lines[] = '';
        }

        $lines[] = 'Analisa SOP:';
        $lines[] = '- Max ETD Gap: ' . ($analysis['max_gap'] ?? 0) . ' hari';
        $lines[] = '- Status: ' . (($analysis['ok'] ?? false) ? 'SESUAI SOP' : 'MELANGGAR SOP');
        $lines[] = '';
        $lines[] = 'Mohon konfirmasi / revisinya.';
        $lines[] = 'Terima kasih.';

        return implode("\n", $lines);
    }

    protected function waGreeting(): string
    {
        $hour = now()->hour;

        return match (true) {
            $hour < 11 => 'Selamat pagi',
            $hour < 15 => 'Selamat siang',
            $hour < 18 => 'Selamat sore',
            default    => 'Selamat malam',
        };
    }

    /* ==============================
     | MODEL EVENTS
     ============================== */

    protected static function booted(): void
    {
        static::creating(function (VesselPlan $plan) {

            if (! config('tam.route.force')) {
                return;
            }

            $polCode = config('tam.route.pol_code');
            $podCode = config('tam.route.pod_code');

            if (! $polCode || ! $podCode) {
                throw new DomainException('Konfigurasi POL / POD TAM belum diatur.');
            }

            $pol = Port::where('code', $polCode)->first();
            $pod = Port::where('code', $podCode)->first();

            if (! $pol || ! $pod) {
                throw new DomainException(
                    "Port TAM tidak ditemukan (POL={$polCode}, POD={$podCode})"
                );
            }

            $plan->pol_id = $pol->id;
            $plan->pod_id = $pod->id;
            $plan->route_code = "{$pol->code}-{$pod->code}";
            $plan->status ??= VesselPlanStatus::Draft;
        });
    }
}
