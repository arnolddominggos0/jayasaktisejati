@php
    $kpi = \App\Services\Operational\VoyageOperationalSnapshot::kpiSummary($rows);

    $groups = \App\Services\Operational\VoyageOperationalSnapshot::categorize($rows);

    $delayed = $groups['delayed'];
    $sailingEtaRisk = $groups['sailingEtaRisk'];
    $sailingNormal = $groups['sailingNormal'];
    $readinessIssue = $groups['readinessIssue'];
    $scheduledNormal = $groups['scheduledNormal'];

    $aktif = $delayed->merge($sailingEtaRisk)->merge($sailingNormal)->merge($readinessIssue)->merge($scheduledNormal);
@endphp

<div class="space-y-8">

    @if ($delayed->count())
        <div>
            <div class="flex items-center gap-2 mb-3">
                {!! OperationalUi::sectionHeading('Terlambat', 'critical') !!}
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
                {!! OperationalUi::sectionHeading('Berlayar — Risiko ETA', 'warning') !!}
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
                {!! OperationalUi::sectionHeading('Berlayar — Normal', 'info') !!}
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
                {!! OperationalUi::sectionHeading('Masalah Kesiapan', 'caution') !!}
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
                {!! OperationalUi::sectionHeading('Terjadwal — Normal', 'normal') !!}
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
