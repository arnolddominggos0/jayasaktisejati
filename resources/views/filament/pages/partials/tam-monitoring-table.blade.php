@php
    use App\Enums\VoyageOperationalStatus;

    $aktif = $rows->filter(fn($v) => $v->operational_status_enum !== VoyageOperationalStatus::COMPLETED);

    $delayed = $aktif->filter(fn($v) => $v->operational_status_enum === VoyageOperationalStatus::DELAYED);

    $sailing = $aktif->filter(fn($v) => $v->operational_status_enum === VoyageOperationalStatus::SAILING);

    $sailingEtaRisk = $sailing->filter(fn($v) => $v->eta_overdue || $v->sailing_risk);
    $sailingNormal = $sailing->reject(fn($v) => $v->eta_overdue || $v->sailing_risk);

    $scheduled = $aktif->filter(fn($v) => $v->operational_status_enum === VoyageOperationalStatus::SCHEDULED);

    $readinessIssue = $scheduled->filter(fn($v) =>
        $v->checkpoints->contains(fn($cp) => !$cp->is_completed && $cp->scheduled_at?->isPast())
        || $v->vesselChecks->contains(fn($vc) => $vc->status?->value === 'potential_delay')
    );

    $scheduledNormal = $scheduled->diff($readinessIssue);
@endphp

<div class="space-y-8">

    @if ($delayed->count())
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-red-600"></span>
                <h2 class="font-bold text-red-700 uppercase text-sm tracking-wide">Terlambat</h2>
            </div>
            <div class="space-y-3">
                @foreach ($delayed as $v)
                    @include('filament.pages.partials.voyage-card-unified', ['v' => $v])
                @endforeach
            </div>
        </div>
    @endif

    @if ($sailingEtaRisk->count())
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-orange-500"></span>
                <h2 class="font-bold text-orange-700 uppercase text-sm tracking-wide">Berlayar — Risiko ETA</h2>
            </div>
            <div class="space-y-3">
                @foreach ($sailingEtaRisk as $v)
                    @include('filament.pages.partials.voyage-card-unified', ['v' => $v])
                @endforeach
            </div>
        </div>
    @endif

    @if ($sailingNormal->count())
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-blue-600"></span>
                <h2 class="font-bold text-blue-700 uppercase text-sm tracking-wide">Berlayar — Normal</h2>
            </div>
            <div class="space-y-3">
                @foreach ($sailingNormal as $v)
                    @include('filament.pages.partials.voyage-card-unified', ['v' => $v])
                @endforeach
            </div>
        </div>
    @endif

    @if ($readinessIssue->count())
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                <h2 class="font-bold text-amber-700 uppercase text-sm tracking-wide">Masalah Kesiapan</h2>
            </div>
            <div class="space-y-3">
                @foreach ($readinessIssue as $v)
                    @include('filament.pages.partials.voyage-card-unified', ['v' => $v])
                @endforeach
            </div>
        </div>
    @endif

    @if ($scheduledNormal->count())
        <div>
            <div class="flex items-center gap-2 mb-3">
                <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
                <h2 class="font-bold text-gray-600 uppercase text-sm tracking-wide">Terjadwal — Normal</h2>
            </div>
            <div class="space-y-3">
                @foreach ($scheduledNormal as $v)
                    @include('filament.pages.partials.voyage-card-unified', ['v' => $v])
                @endforeach
            </div>
        </div>
    @endif

    @if (!$aktif->count())
        <div class="bg-white border rounded-2xl p-8 text-center text-gray-500">
            Tidak ada pelayaran aktif pada periode ini.
        </div>
    @endif

</div>
