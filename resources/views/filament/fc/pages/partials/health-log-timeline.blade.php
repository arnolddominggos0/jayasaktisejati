{{-- SC.5D.2E — Health Log Timeline --}}
{{-- $state = Collection<AttendanceHealthLog> with attendance.manpower eager-loaded --}}

@if($state->isEmpty())
    <p class="text-sm text-gray-400 dark:text-gray-500 italic">Belum ada riwayat pemeriksaan kesehatan.</p>
@else
<div class="flow-root">
    <ul role="list" class="-mb-8">
        @foreach($state as $log)
        @php
            $colorMap = [
                'auto_fit'        => ['dot' => 'bg-green-500',  'badge' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
                'recheck_fit'     => ['dot' => 'bg-green-500',  'badge' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300'],
                'auto_not_fit'    => ['dot' => 'bg-red-500',    'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'],
                'recheck_not_fit' => ['dot' => 'bg-red-500',    'badge' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300'],
                'medical_action'  => ['dot' => 'bg-amber-500',  'badge' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300'],
                'recheck_started' => ['dot' => 'bg-blue-500',   'badge' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300'],
                'initial_check'   => ['dot' => 'bg-gray-400',   'badge' => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300'],
            ];
            $colors = $colorMap[$log->event_type] ?? $colorMap['initial_check'];

            $labelMap = [
                'initial_check'   => 'Pemeriksaan Awal',
                'auto_fit'        => 'Evaluasi: FIT',
                'auto_not_fit'    => 'Evaluasi: Tidak FIT',
                'medical_action'  => 'Tindakan Medis',
                'recheck_started' => 'Recheck Dimulai',
                'recheck_fit'     => 'Recheck: FIT',
                'recheck_not_fit' => 'Recheck: Tidak FIT',
            ];
            $label = $labelMap[$log->event_type] ?? $log->event_type;

            $mpName = $log->attendance?->manpower?->name
                ?? ($log->attendance?->backup_name ?? 'MP#' . $log->attendance_id);
        @endphp
        <li>
            <div class="relative pb-8">
                @if(!$loop->last)
                <span class="absolute left-4 top-4 -ml-px h-full w-0.5 bg-gray-200 dark:bg-gray-700" aria-hidden="true"></span>
                @endif
                <div class="relative flex space-x-3">
                    {{-- Timeline dot --}}
                    <div>
                        <span class="h-8 w-8 rounded-full {{ $colors['dot'] }} flex items-center justify-center ring-4 ring-white dark:ring-gray-900">
                            @if(in_array($log->event_type, ['auto_fit', 'recheck_fit']))
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            @elseif(in_array($log->event_type, ['auto_not_fit', 'recheck_not_fit']))
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                            @elseif($log->event_type === 'medical_action')
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"/></svg>
                            @else
                                <svg class="h-4 w-4 text-white" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z"/></svg>
                            @endif
                        </span>
                    </div>

                    {{-- Content --}}
                    <div class="flex min-w-0 flex-1 justify-between space-x-4 pt-1.5">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="text-xs font-semibold text-gray-900 dark:text-white">{{ $mpName }}</span>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium {{ $colors['badge'] }}">
                                    {{ $label }}
                                </span>
                            </div>

                            {{-- Vitals --}}
                            @if($log->temperature || $log->bp_systolic)
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                                @if($log->temperature)Suhu: {{ number_format((float)$log->temperature, 1) }}°C @endif
                                @if($log->bp_systolic) &nbsp;·&nbsp; TD: {{ $log->bp_systolic }}/{{ $log->bp_diastolic }} mmHg @endif
                            </p>
                            @endif

                            {{-- Medical action --}}
                            @if($log->medical_action)
                            <p class="mt-0.5 text-xs text-gray-700 dark:text-gray-300 font-medium">
                                Tindakan: {{ $log->medical_action }}
                            </p>
                            @endif

                            {{-- Remark --}}
                            @if($log->remark)
                            <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400 italic">{{ $log->remark }}</p>
                            @endif
                        </div>

                        {{-- Timestamp --}}
                        <div class="whitespace-nowrap text-right text-xs text-gray-400 dark:text-gray-500">
                            {{ $log->created_at?->translatedFormat('d M H:i') ?? '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </li>
        @endforeach
    </ul>
</div>
@endif
