@php
    $events = collect();

    foreach ($checks as $check) {
        $events->push([
            'at'    => $check->checked_at,
            'kind'  => 'check',
            'model' => $check,
        ]);

        foreach ($check->medicalActions as $action) {
            $events->push([
                'at'    => $action->performed_at,
                'kind'  => 'action',
                'model' => $action,
            ]);
        }
    }

    $events = $events->sortBy('at')->values();
@endphp

@if ($events->isEmpty())
    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Belum ada riwayat pemeriksaan.</p>
@else
    <div class="flow-root">
        <ul role="list" class="-mb-8">
            @foreach ($events as $event)
                @php
                    $isCheck = $event['kind'] === 'check';
                    $model   = $event['model'];

                    if ($isCheck) {
                        $isFit  = strtoupper((string) $model->status) === 'FIT';
                        $isNotFit = strtoupper((string) $model->status) === 'TIDAK FIT';
                        $dot    = $isFit ? 'bg-emerald-500' : ($isNotFit ? 'bg-rose-500' : 'bg-gray-400');
                        $badge  = $isFit
                            ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-300'
                            : ($isNotFit
                                ? 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-300'
                                : 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300');
                        $label  = $model->type_label;
                    } else {
                        $dot   = 'bg-amber-500';
                        $badge = 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300';
                        $label = 'Tindakan Medis';
                    }
                @endphp
                <li>
                    <div class="relative pb-8">
                        @if (! $loop->last)
                            <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                        @endif
                        <div class="relative flex space-x-3">
                            <div>
                                <span class="h-8 w-8 rounded-full {{ $dot }} flex items-center justify-center ring-4 ring-white dark:ring-gray-900">
                                    @if ($isCheck)
                                        <x-heroicon-o-clipboard-document-check class="h-4 w-4 text-white" />
                                    @else
                                        <x-heroicon-o-heart class="h-4 w-4 text-white" />
                                    @endif
                                </span>
                            </div>

                            <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                                <div>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $badge }}">
                                        {{ $label }}
                                    </span>

                                    @if ($isCheck)
                                        <p class="mt-1 text-xs text-gray-600 dark:text-gray-400">
                                            @if ($model->temperature)
                                                Suhu: {{ number_format((float) $model->temperature, 1) }}°C
                                            @endif
                                            @if ($model->bp_systolic)
                                                &nbsp;·&nbsp; TD: {{ $model->bp }} mmHg
                                            @endif
                                            @if ($model->status)
                                                &nbsp;·&nbsp; Status: {{ $model->status }}
                                            @endif
                                        </p>
                                        @if ($model->health_complaint)
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 italic">{{ $model->health_complaint }}</p>
                                        @endif
                                    @else
                                        <p class="mt-1 text-xs font-medium text-gray-700 dark:text-gray-300">
                                            {{ $model->action }}
                                        </p>
                                        @if ($model->notes)
                                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 italic">{{ $model->notes }}</p>
                                        @endif
                                    @endif
                                </div>

                                <div class="whitespace-nowrap text-right text-xs text-gray-400 dark:text-gray-500">
                                    {{ $event['at']?->translatedFormat('d M H:i') ?? '—' }}
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endif
