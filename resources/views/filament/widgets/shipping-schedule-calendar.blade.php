<x-filament::widget>
    <x-filament::card>
        {{-- Header --}}
        <div class="mb-4">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-3">
                    <h2 class="text-base font-semibold text-gray-900 dark:text-white">
                        📅 Jadwal Kapal {{ $periodLabel }}
                    </h2>
                    <span class="px-2 py-0.5 text-xs font-medium text-green-800 bg-green-100 rounded">
                        Final
                    </span>
                </div>
            </div>

            {{-- Summary Cards --}}
            <div class="grid grid-cols-4 gap-2 mb-3">
                <div class="p-2.5 bg-blue-50 rounded border border-blue-200">
                    <div class="text-xs text-blue-600 font-medium">Total Kapal</div>
                    <div class="text-xl font-bold text-blue-900 mt-0.5">{{ $totalVessels }}</div>
                    <div class="text-xs text-blue-600">voyage</div>
                </div>
                <div class="p-2.5 bg-orange-50 rounded border border-orange-200">
                    <div class="text-xs text-orange-600 font-medium">Total Kapasitas</div>
                    <div class="text-xl font-bold text-orange-900 mt-0.5">{{ number_format($totalCapacity) }}</div>
                    <div class="text-xs text-orange-600">unit</div>
                </div>
                <div class="p-2.5 bg-green-50 rounded border border-green-200">
                    <div class="text-xs text-green-600 font-medium">Rata-rata/Minggu</div>
                    <div class="text-xl font-bold text-green-900 mt-0.5">
                        {{ $totalVessels > 0 ? number_format($totalVessels / 4, 1) : '0' }}</div>
                    <div class="text-xs text-green-600">kapal</div>
                </div>
                <div class="p-2.5 bg-purple-50 rounded border border-purple-200">
                    <div class="text-xs text-purple-600 font-medium">Status Jadwal</div>
                    <div class="text-base font-bold text-purple-900 mt-0.5">Final</div>
                    <div class="text-xs text-purple-600">Siap digunakan</div>
                </div>
            </div>

            {{-- Week Stats --}}
            <div class="grid grid-cols-4 gap-2 mb-3">
                @foreach ($weekStats as $week => $stat)
                    <div class="p-2 bg-gray-50 rounded border border-gray-200">
                        <div class="text-xs text-gray-600 font-medium">Week {{ $week }} ({{ $stat['range'] }})
                        </div>
                        <div class="text-xs font-semibold text-gray-900 mt-0.5">
                            {{ $stat['vessels'] }} kapal • {{ number_format($stat['capacity']) }} unit
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <style>
            .timeline-container {
                overflow-x: auto;
                border: 1px solid #e5e7eb;
                border-radius: 6px;
                background: #fff;
            }

            .timeline-grid {
                display: grid;
                grid-template-columns: 160px 1fr;
                min-width: 100%;
            }

            .timeline-header {
                display: grid;
                grid-template-columns: repeat({{ $totalDays }}, 1fr);
                border-bottom: 2px solid #e5e7eb;
                background: #f9fafb;
                position: sticky;
                top: 0;
                z-index: 10;
            }

            .timeline-day {
                padding: 6px 2px;
                text-align: center;
                border-right: 1px solid #f3f4f6;
                font-size: 0.65rem;
                min-width: 0;
            }

            .timeline-day-num {
                display: block;
                font-weight: 600;
                color: #111827;
                font-size: 0.75rem;
                line-height: 1.2;
            }

            .timeline-day-name {
                display: block;
                color: #6b7280;
                font-size: 0.55rem;
                text-transform: uppercase;
                margin-top: 1px;
                line-height: 1;
            }

            .timeline-day.weekend {
                background: #fef2f2;
            }

            .timeline-day.weekend .timeline-day-num {
                color: #dc2626;
            }

            .timeline-day.today {
                background: #fef3c7;
                border-left: 2px solid #f59e0b;
                border-right: 2px solid #f59e0b;
                position: relative;
            }

            .timeline-day.today::after {
                content: '';
                position: absolute;
                top: 100%;
                left: 50%;
                width: 2px;
                height: 2000px;
                background: #f59e0b;
                opacity: 0.3;
                pointer-events: none;
            }

            .vessel-label {
                padding: 8px;
                border-right: 1px solid #e5e7eb;
                border-bottom: 1px solid #f3f4f6;
                background: #fafafa;
                position: sticky;
                left: 0;
                z-index: 5;
                font-size: 0.75rem;
            }

            .vessel-name {
                font-weight: 600;
                font-size: 0.75rem;
                color: #111827;
                margin-bottom: 2px;
                line-height: 1.2;
            }

            .vessel-info {
                font-size: 0.65rem;
                color: #6b7280;
                display: flex;
                align-items: center;
                gap: 3px;
                margin-top: 1px;
                line-height: 1.2;
            }

            .timeline-track {
                display: grid;
                grid-template-columns: repeat({{ $totalDays }}, 1fr);
                border-bottom: 1px solid #f3f4f6;
                position: relative;
                min-height: 44px;
                align-items: center;
            }

            .timeline-bar {
                position: absolute;
                height: 28px;
                border-radius: 5px;
                display: flex;
                align-items: center;
                padding: 0 6px;
                font-size: 0.65rem;
                font-weight: 600;
                color: white;
                cursor: pointer;
                transition: all 0.2s;
                overflow: hidden;
                white-space: nowrap;
                box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
            }

            .timeline-bar:hover {
                transform: translateY(-2px);
                box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
                z-index: 20;
                height: 32px;
            }

            .timeline-bar-default {
                background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%);
                border: 2px solid #1e40af;
            }

            .bar-label {
                display: flex;
                align-items: center;
                gap: 4px;
                width: 100%;
            }

            .bar-icon {
                font-size: 0.75rem;
                flex-shrink: 0;
            }

            .bar-text {
                flex: 1;
                overflow: hidden;
                text-overflow: ellipsis;
            }

            .timeline-grid-bg {
                display: grid;
                grid-template-columns: repeat({{ $totalDays }}, 1fr);
                position: absolute;
                width: 100%;
                height: 100%;
                pointer-events: none;
            }

            .grid-line {
                border-right: 1px solid #f3f4f6;
            }

            .grid-line.weekend {
                background: #fef2f2;
            }

            .grid-line.today {
                background: #fef3c7;
                border-left: 2px solid #f59e0b;
                border-right: 2px solid #f59e0b;
            }

            .empty-state {
                grid-column: 1 / -1;
                text-align: center;
                padding: 60px 20px;
                color: #9ca3af;
            }

            .empty-state-icon {
                font-size: 3rem;
                margin-bottom: 12px;
            }

            @media print {
                .timeline-day.today::after {
                    display: none;
                }
            }
        </style>

        <div class="timeline-container">
            <div class="timeline-grid">
                {{-- Header: Vessel Column --}}
                <div
                    style="background: #fff; border-bottom: 2px solid #e5e7eb; padding: 8px; font-weight: 600; color: #374151; position: sticky; top: 0; z-index: 11; font-size: 0.75rem;">
                    Kapal & Voyage
                </div>

                {{-- Header: Timeline Days --}}
                <div class="timeline-header">
                    @foreach ($days as $day)
                        <div
                            class="timeline-day {{ $day['isWeekend'] ? 'weekend' : '' }} {{ $day['isToday'] ? 'today' : '' }}">
                            <span class="timeline-day-num">{{ $day['day'] }}</span>
                            <span class="timeline-day-name">{{ $day['dayName'] }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Body: Vessel Rows --}}
                @forelse ($vessels as $vessel)
                    {{-- Vessel Label --}}
                    <div class="vessel-label">
                        <div class="vessel-name" title="{{ $vessel['name'] }}">
                            {{ $vessel['code'] }}
                            @if ($vessel['voyage'])
                                <span
                                    style="color: #6b7280; font-weight: 400; font-size: 0.75rem;">{{ $vessel['voyage'] }}</span>
                            @endif
                        </div>
                        <div class="vessel-info">
                            <span>📦 {{ number_format($vessel['cargo']) }}</span>
                        </div>
                        <div class="vessel-info">
                            <span>{{ $vessel['etd']->format('d M') }} → {{ $vessel['eta']->format('d M') }}</span>
                        </div>
                    </div>

                    {{-- Timeline Track --}}
                    <div class="timeline-track">
                        {{-- Background Grid --}}
                        <div class="timeline-grid-bg">
                            @foreach ($days as $day)
                                <div
                                    class="grid-line {{ $day['isWeekend'] ? 'weekend' : '' }} {{ $day['isToday'] ? 'today' : '' }}">
                                </div>
                            @endforeach
                        </div>

                        {{-- Timeline Bar --}}
                        <div class="timeline-bar timeline-bar-default"
                            style="left: calc({{ ($vessel['start_offset'] / $totalDays) * 100 }}% + 2px); 
                                    width: calc({{ ($vessel['duration'] / $totalDays) * 100 }}% - 4px);"
                            title="📋 {{ $vessel['name'] }}&#10;🔢 {{ $vessel['voyage'] }}&#10;🚢 ETD: {{ $vessel['etd']->format('d M Y H:i') }}&#10;🏁 ETA: {{ $vessel['eta']->format('d M Y H:i') }}&#10;📦 Cargo: {{ number_format($vessel['cargo']) }} unit&#10;⏱️ Durasi: {{ $vessel['duration'] }} hari">
                            <div class="bar-label">
                                <span class="bar-icon">🚢</span>
                                <span class="bar-text">
                                    {{ $vessel['code'] }}
                                    @if ($vessel['voyage'])
                                        {{ $vessel['voyage'] }}
                                    @endif
                                </span>
                                <span class="bar-icon">🏁</span>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <div class="font-semibold text-gray-700 mb-1">Tidak ada jadwal kapal</div>
                        <div class="text-sm text-gray-500">Belum ada jadwal final untuk periode {{ $periodLabel }}
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Footer Info --}}
        <div class="mt-3 flex items-center justify-between text-xs">
            <div class="flex items-center gap-3 text-gray-600">
                <div class="flex items-center gap-1.5">
                    <div
                        style="width: 16px; height: 16px; background: linear-gradient(135deg, #3b82f6 0%, #1e40af 100%); border-radius: 3px;">
                    </div>
                    <span>Perjalanan (ETD → ETA)</span>
                </div>
                @if ($totalVessels > 0)
                    <div class="text-gray-500">
                        <span class="font-semibold text-gray-900">{{ $totalVessels }}</span> kapal •
                        <span class="font-semibold text-gray-900">{{ number_format($totalCapacity) }}</span> unit
                    </div>
                @endif
            </div>
            <div class="text-gray-500">
                💡 Hover pada bar untuk detail lengkap
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
