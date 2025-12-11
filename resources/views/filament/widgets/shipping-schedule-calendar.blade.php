@php
$d = $this->getData();
$completion = $d['kpi']['completion'] ?? 0;
@endphp

<div class="space-y-4" x-data="calendarComponent()" x-init="init()">
    <div class="bg-white rounded-xl shadow-sm border p-5">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
            <div class="flex-1 space-y-2">
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wide">Monitoring Jadwal Kapal TAM — Rute: {{ strtoupper(config('tam.route.pol_code', 'JKT')) }} → {{ strtoupper(config('tam.route.pod_code', 'BTG')) }}</div>
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <h2 class="text-lg font-semibold text-gray-900">Kalender Jadwal Kapal</h2>
                    <span class="text-xs text-gray-600">Periode {{ $d['month_label'] ?? '' }} · SLA Sailing ≤ 11 hari (ETD → ATA)</span>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 mt-1">
                    <div class="rounded-lg p-2.5 border border-blue-200 bg-blue-50/80">
                        <div class="text-[10px] font-medium text-blue-700">Total Cargo Plan</div>
                        <div class="mt-0.5 text-xl font-semibold text-blue-900">{{ number_format($d['total_plan'] ?? 0) }}</div>
                        <div class="mt-0.5 text-[9px] text-blue-700">unit dengan ETD bulan ini</div>
                    </div>

                    <div class="rounded-lg p-2.5 border border-gray-200 bg-gray-50/80">
                        <div class="text-[10px] font-medium text-gray-700">Voyage selesai</div>
                        <div class="mt-0.5 text-xl font-semibold text-gray-900">{{ $d['kpi']['total'] ?? 0 }}</div>
                        <div class="mt-0.5 text-[9px] text-gray-600">voyage final</div>
                    </div>

                    <div class="rounded-lg p-2.5 border border-emerald-200 bg-emerald-50/80">
                        <div class="text-[10px] font-medium text-emerald-700">Tepat waktu</div>
                        <div class="mt-0.5 text-xl font-semibold text-emerald-900">{{ $d['kpi']['on_time'] ?? 0 }}</div>
                        <div class="mt-0.5 text-[9px] text-emerald-700">durasi ≤ 11 hari</div>
                    </div>

                    <div class="rounded-lg p-2.5 border border-rose-200 bg-rose-50/80">
                        <div class="text-[10px] font-medium text-rose-700">Terlambat</div>
                        <div class="mt-0.5 text-xl font-semibold text-rose-900">{{ $d['kpi']['late'] ?? 0 }}</div>
                        <div class="mt-0.5 text-[9px] text-rose-700">durasi &gt; 11 hari</div>
                    </div>
                </div>

                <div class="mt-2 rounded-lg border border-blue-200 bg-gradient-to-r from-blue-50 to-indigo-50 p-3">
                    <div class="flex items-center justify-between gap-3 mb-1.5">
                        <div class="flex flex-col">
                            <span class="text-xs font-semibold text-gray-900">Pencapaian SLA Sailing</span>
                            <span class="text-[10px] text-gray-600">Persentase voyage ≤ 11 hari</span>
                        </div>
                        <span class="text-2xl font-bold text-blue-600">{{ $completion }}%</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2.5 overflow-hidden">
                        @php
                        $barColor = $completion >= 80 ? 'bg-gradient-to-r from-emerald-500 to-emerald-600' : 'bg-gradient-to-r from-orange-500 to-orange-600';
                        @endphp
                        <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-500" style="width: {{ $completion }}%"></div>
                    </div>
                </div>
            </div>

            <div class="flex-shrink-0 w-full lg:w-56">
                <div class="bg-gray-50 rounded-lg p-3.5 border border-gray-200">
                    <div class="text-[11px] font-medium text-gray-700 mb-1.5">Pilih periode</div>
                    <div class="flex flex-col gap-1.5">
                        <select wire:model.live="monthNum" class="w-full rounded-lg border-gray-300 text-xs">
                            @foreach ($d['month_options'] as $num => $label)
                            <option value="{{ $num }}">{{ $label }}</option>
                            @endforeach
                        </select>

                        <select wire:model.live="year" class="w-full rounded-lg border-gray-300 text-xs">
                            @foreach ($d['year_options'] as $yy)
                            <option value="{{ $yy }}">{{ $yy }}</option>
                            @endforeach
                        </select>

                        <button type="button" wire:click="$set('year', {{ now()->year }}); $set('monthNum', {{ now()->month }})" class="w-full px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 hover:bg-blue-700 rounded-lg">Bulan ini</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
        <div class="relative">
            <div class="overflow-x-auto scrollbar-thin relative" id="calendar-scroll-{{ md5($d['month_label'] ?? '') }}">
                <div class="bars-overlay absolute inset-0 pointer-events-none z-10"></div>

                @php
                $daysCount = $d['days_count'] ?? 30;
                $lanes = array_keys($d['lanes'] ?? []);
                $laneIndexMap = [];
                foreach ($lanes as $idx => $k) { $laneIndexMap[$k] = $idx; }
                $bars = $d['bars'] ?? [];
                $overrides = config('vessel_code.overrides', config('vessel.overrides', []));
                @endphp

                <div class="absolute inset-0 pointer-events-none z-20">
                    @foreach ($bars as $bar)
                        @php
                            $from = max(1, (int)($bar['from'] ?? 1));
                            $span = max(1, (int)($bar['span'] ?? 1));
                            $leftPct = ($from - 1) / max(1, $daysCount) * 100;
                            $widthPct = ($span) / max(1, $daysCount) * 100;
                            $barLane = $bar['lane'] ?? null;
                            if ($barLane === null) {
                                $fallback = in_array('act_atd', $lanes) ? 'act_atd' : $lanes[0] ?? null;
                                $barLane = $fallback;
                            }
                            $laneIdx = $laneIndexMap[$barLane] ?? 0;
                            $barClass = $bar['class'] ?? 'bg-blue-100 text-blue-800 border border-blue-200';
                            $chipId = $bar['meta']['id'] ?? ($bar['id'] ?? ($bar['schedule_id'] ?? uniqid('chip_')));

                            $meta = $bar['meta'] ?? [];
                            if (is_object($meta)) $meta = (array) $meta;

                            $voyNo = $bar['voyage_no'] ?? ($meta['voyage_no'] ?? null);
                            $vesselName = $meta['vessel_name'] ?? ($bar['vessel_name'] ?? '');
                            $vesselCodeMeta = $meta['vessel_code'] ?? ($bar['vessel_code'] ?? '');

                            $shipShort = null;
                            foreach ($overrides as $k => $v) {
                                if ($k !== '' && stripos($vesselName, $k) !== false) { $shipShort = strtoupper($v); break; }
                            }
                            if (!$shipShort) {
                                if (str_contains($vesselCodeMeta, '-')) {
                                    $p = explode('-', $vesselCodeMeta);
                                    $shipShort = strtoupper(trim(end($p)));
                                } else {
                                    $parts = preg_split('/[^A-Z0-9]+/i', strtoupper($vesselName));
                                    $pieces = [];
                                    foreach ($parts as $i => $p) {
                                        if ($p === '') continue;
                                        $pieces[] = substr($p, 0, ($i === 0 ? 3 : 2));
                                        if (count($pieces) >= 3) break;
                                    }
                                    $shipShort = substr(implode('', $pieces), 0, 4);
                                }
                            }

                            $linePrefix = '';
                            if (!empty($vesselCodeMeta) && str_contains($vesselCodeMeta, '-')) {
                                $parts = explode('-', $vesselCodeMeta);
                                $linePrefix = strtoupper(substr(trim($parts[0]), 0, 2));
                            } elseif (!empty($vesselCodeMeta) && strlen($vesselCodeMeta) >= 2) {
                                $linePrefix = strtoupper(substr($vesselCodeMeta, 0, 2));
                            } else {
                                $linePrefix = strtoupper(substr($vesselName, 0, 2));
                            }

                            $shortComputed = trim($linePrefix . ($shipShort ? $shipShort : ''), '- ');
                            $shortComputed = preg_replace('/[^A-Z0-9]/', '', strtoupper($shortComputed));
                            $shortDisplay = $shortComputed ?: strtoupper($meta['short'] ?? ($bar['short'] ?? 'VS'));
                            $plan = (int)($bar['plan'] ?? ($meta['plan'] ?? 0));
                            $label = $bar['label'] ?? ($meta['label'] ?? $shortDisplay);
                            $head = $meta['head'] ?? ($bar['head'] ?? $label);
                            $sub = $meta['sub'] ?? ($bar['sub'] ?? ($plan ? ($plan . ' unit') : ''));
                            $payload = json_encode([
                                'id' => $chipId,
                                'short' => $shortDisplay,
                                'voyage_no' => $voyNo,
                                'label' => $label,
                                'voyages' => $meta['voyages'] ?? ($bar['voyages'] ?? []),
                                'count' => $meta['count'] ?? ($bar['count'] ?? 1),
                                'plan' => $plan,
                                'lead' => isset($bar['lead']) ? $bar['lead'] : ($meta['lead'] ?? null),
                                'lead_time' => isset($bar['lead_time']) ? $bar['lead_time'] : ($meta['lead_time'] ?? null),
                                'class' => $bar['class'] ?? ($meta['class'] ?? $barClass),
                                'is_urgent' => (bool)($bar['is_urgent'] ?? ($meta['is_urgent'] ?? false)),
                                'sla_status' => $bar['sla_status'] ?? ($meta['sla_status'] ?? null),
                                'voyage_id' => $bar['voyage_id'] ?? ($meta['voyage_id'] ?? null),
                                'schedule_id' => $bar['schedule_id'] ?? ($meta['schedule_id'] ?? null),
                                'head' => $head,
                                'sub' => $sub,
                            ], JSON_HEX_APOS|JSON_HEX_QUOT);
                            $vesselKey = $shortDisplay;
                            $chipLabelCompact = $shortDisplay . ($voyNo ? '/' . $voyNo : '') . ($plan ? ' (' . $plan . 'u)' : '');
                        @endphp

                        <div
                            class="absolute calendar-bar pointer-events-auto rounded-lg px-3 py-1.5 text-[12px] font-semibold shadow-sm flex items-center gap-2 {{ $barClass }}"
                            style="left: {{ $leftPct }}%; width: {{ $widthPct }}%; z-index: 40; transform-origin: left center;"
                            role="button"
                            tabindex="0"
                            data-bar-lane-index="{{ $laneIdx }}"
                            data-chip-day="{{ $from }}"
                            data-chip-id="{{ $chipId }}"
                            data-vessel-key="{{ $vesselKey }}"
                            x-on:mouseenter="(e) => showHover(e, {!! $payload !!})"
                            x-on:mouseleave="hideHover()"
                            x-on:click="$dispatch('jss-show-chip', {!! $payload !!}); openModal({!! $payload !!})"
                            title="{{ $label }}">
                            <div class="flex items-center gap-2 truncate" style="min-width:0;">
                                <div class="text-sm font-bold leading-none uppercase" style="letter-spacing:0.6px; white-space:nowrap;">{{ $chipLabelCompact }}</div>
                            </div>

                            @if (!empty($plan))
                                <span class="ml-auto text-[11px] px-2 py-0.5 bg-white/80 rounded text-gray-700">{{ $plan }}u</span>
                            @endif
                        </div>
                    @endforeach
                </div>

                <div class="min-w-[1200px]">
                    <div class="max-h-[540px] overflow-y-auto">
                        <table class="w-full border-collapse text-[11px] table-fixed">
                            <thead class="sticky top-0 z-30 bg-white shadow-sm">
                                <tr>
                                    <th class="sticky left-0 z-40 bg-gray-100 border-b border-r px-3 py-2 text-left w-48 min-w-[12rem]">
                                        <div class="text-xs font-semibold text-gray-900">Rute (POL → POD)</div>
                                    </th>

                                    @foreach ($d['days'] ?? [] as $day)
                                        @php
                                            $isWeekend = !empty($day['isWeekend']);
                                            $isToday = (($day['date'] ?? null) === ($d['today'] ?? ''));
                                            $weekendClass = $isWeekend ? 'bg-rose-50' : 'bg-gray-50';
                                            $todayRing = $isToday ? 'ring-2 ring-blue-600 ring-inset' : '';
                                            $dowClass = $isWeekend ? 'text-rose-700' : 'text-gray-500';
                                            $numClass = $isToday ? 'text-blue-700' : ($isWeekend ? 'text-rose-700' : 'text-gray-900');
                                        @endphp

                                        <th class="border-b border-gray-300 text-center px-2 py-1.5 {{ $weekendClass }} {{ $todayRing }} w-16 min-w-[4rem]" title="{{ $day['date'] ?? '' }}">
                                            <div class="text-[9px] font-semibold uppercase tracking-wide {{ $dowClass }}">{{ $day['dow'] ?? '' }}</div>
                                            <div class="text-sm font-bold mt-0.5 {{ $numClass }}">{{ $day['n'] ?? '' }}</div>
                                        </th>
                                    @endforeach
                                </tr>
                            </thead>

                            <tbody>
                                @foreach ($d['lanes'] ?? [] as $key => $label)
                                    @php
                                        $laneType = $d['lane_types'][$key] ?? 'plan';
                                        $laneBg = match ($laneType) {
                                            'plan'    => 'bg-slate-100',
                                            'actual'  => 'bg-sky-50',
                                            'summary' => 'bg-amber-100',
                                            default   => 'bg-gray-100',
                                        };
                                    @endphp

                                    <tr class="hover:bg-gray-50 transition-colors" data-lane-index="{{ $loop->index }}">
                                        <td class="sticky left-0 z-40 border-b border-r px-3 py-2.5 {{ $laneBg }} w-48 min-w-[12rem]">
                                            <div class="flex flex-col gap-0.5">
                                                <span class="text-xs font-semibold">{{ $label }}</span>
                                                @if ($laneType === 'plan')
                                                    <span class="text-[9px] text-gray-700">Estimasi jendela keberangkatan / kedatangan kapal.</span>
                                                @elseif ($laneType === 'actual')
                                                    <span class="text-[9px] text-gray-700">Realisasi sailing (dipakai hitung SLA TAM).</span>
                                                @else
                                                    <span class="text-[9px] text-gray-700">Total unit berangkat per hari berdasarkan ATD.</span>
                                                @endif
                                            </div>
                                        </td>

                                        @for ($i = 1; $i <= ($d['days_count'] ?? 0); $i++)
                                            @php
                                            $chips=$d['bucket'][$key][$i] ?? [];
                                            $hasSailing=!empty($d['sailing_bg'][$i]);
                                            @endphp

                                            <td class="border-b border-gray-200 align-top relative w-16 min-w-[4rem]">
                                            @if ($hasSailing)
                                            <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-sky-50 to-indigo-50 opacity-60 pointer-events-none"></div>
                                            @endif

                                            <div class="relative z-10 px-0.5 py-0.5 space-y-0.5 min-h-[70px] max-h-[120px] overflow-y-auto">
                                                @if (empty($chips))
                                                <div class="text-[10px] text-gray-400 text-center py-3">—</div>
                                                @endif

                                                @foreach ($chips as $chip)
                                                @php
                                                $meta = $chip['meta'] ?? [];
                                                if (is_object($meta)) $meta = (array) $meta;

                                                $chipId = $meta['id'] ?? ($chip['id'] ?? uniqid('chip_'));
                                                $voyageNo = $meta['voyage_no'] ?? ($chip['voyage_no'] ?? null);
                                                $vesselName = $meta['vessel_name'] ?? '';
                                                $vesselCodeMeta = $meta['vessel_code'] ?? '';
                                                $short = $chip['short'] ?? ($chip['label'] ?? '');

                                                $shipShort = null;
                                                foreach ($overrides as $k => $v) {
                                                    if ($k !== '' && stripos($vesselName, $k) !== false) { $shipShort = strtoupper($v); break; }
                                                }
                                                if (!$shipShort) {
                                                    if (str_contains($vesselCodeMeta, '-')) {
                                                        $p = explode('-', $vesselCodeMeta);
                                                        $shipShort = strtoupper(trim(end($p)));
                                                    } else {
                                                        $parts = preg_split('/[^A-Z0-9]+/i', strtoupper($vesselName));
                                                        $pieces = [];
                                                        foreach ($parts as $ii => $p) {
                                                            if ($p === '') continue;
                                                            $pieces[] = substr($p, 0, ($ii === 0 ? 3 : 2));
                                                            if (count($pieces) >= 3) break;
                                                        }
                                                        $shipShort = substr(implode('', $pieces), 0, 4);
                                                    }
                                                }
                                                $linePrefix = '';
                                                if (!empty($vesselCodeMeta) && str_contains($vesselCodeMeta, '-')) {
                                                    $parts = explode('-', $vesselCodeMeta);
                                                    $linePrefix = strtoupper(substr(trim($parts[0]), 0, 2));
                                                } elseif (!empty($vesselCodeMeta) && strlen($vesselCodeMeta) >= 2) {
                                                    $linePrefix = strtoupper(substr($vesselCodeMeta, 0, 2));
                                                } else {
                                                    $linePrefix = strtoupper(substr($vesselName, 0, 2));
                                                }
                                                $shortComputed = trim($linePrefix . ($shipShort ? $shipShort : ''), '- ');
                                                $shortComputed = preg_replace('/[^A-Z0-9]/', '', strtoupper($shortComputed));
                                                $shortDisplay = $shortComputed ?: strtoupper($meta['short'] ?? ($chip['short'] ?? 'VS'));
                                                $plan = (int)($chip['plan'] ?? ($meta['plan'] ?? 0));
                                                $label = $chip['label'] ?? $shortDisplay;
                                                $count = $chip['count'] ?? ($meta['count'] ?? 1);
                                                $lead = isset($chip['lead']) ? $chip['lead'] : ($meta['lead'] ?? null);
                                                $lead_time = isset($chip['lead_time']) ? $chip['lead_time'] : ($meta['lead_time'] ?? null);
                                                $chipClass = $chip['class'] ?? ($meta['class'] ?? 'bg-white text-gray-800 border border-gray-200');
                                                $isUrgent = (bool)($chip['is_urgent'] ?? ($meta['is_urgent'] ?? false));
                                                $slaStatus = $chip['sla_status'] ?? ($meta['sla_status'] ?? null);
                                                $voyageId = isset($chip['voyage_id']) ? $chip['voyage_id'] : ($meta['voyage_id'] ?? null);
                                                $scheduleId = isset($chip['schedule_id']) ? $chip['schedule_id'] : ($meta['schedule_id'] ?? null);
                                                $head = $chip['head'] ?? ($meta['head'] ?? strtoupper($shortDisplay));
                                                $sub = $chip['sub'] ?? ($meta['sub'] ?? ($plan ? ($plan . ' unit') : ''));

                                                $chipLabelCompact = $shortDisplay . ($voyageNo ? '/' . $voyageNo : '') . ($plan ? ' (' . $plan . 'u)' : '');

                                                $payload = json_encode([
                                                'id' => $chipId,
                                                'short' => $shortDisplay,
                                                'voyage_no' => $voyageNo,
                                                'label' => $label,
                                                'voyages' => $meta['voyages'] ?? ($chip['voyages'] ?? []),
                                                'count' => $count,
                                                'plan' => $plan,
                                                'lead' => $lead,
                                                'lead_time' => $lead_time,
                                                'class' => $chipClass,
                                                'is_urgent' => $isUrgent,
                                                'sla_status' => $slaStatus,
                                                'voyage_id' => $voyageId,
                                                'schedule_id' => $scheduleId,
                                                'head' => $head,
                                                'sub' => $sub,
                                                ], JSON_HEX_APOS|JSON_HEX_QUOT);

                                                $vesselKey = $shortDisplay;
                                                @endphp

                                                <div
                                                    x-on:mouseenter="(e) => showHover(e, {!! $payload !!})"
                                                    x-on:mouseleave="hideHover()"
                                                    x-on:click="$dispatch('jss-show-chip', {!! $payload !!}); openModal({!! $payload !!})"
                                                    role="button"
                                                    tabindex="0"
                                                    class="chip inline-flex items-center justify-center px-3 py-1.5 rounded-md cursor-pointer shadow-sm bg-white text-gray-800 border border-gray-200 hover:shadow-md hover:scale-105 transition-all duration-150 {{ $chipClass }}"
                                                    data-chip-id="{{ $chipId }}"
                                                    data-vessel-key="{{ $vesselKey }}"
                                                    data-plan="{{ $plan }}"
                                                    title="{{ $label }}">
                                                    <div class="flex items-center gap-3">
                                                        <div class="w-2.5 h-8 rounded-sm flex-shrink-0 chip-color-indicator"></div>

                                                        <div class="flex items-baseline gap-2">
                                                            <div class="text-[12px] font-semibold truncate uppercase" style="letter-spacing:0.6px; max-width: 140px;">{{ $chipLabelCompact }}</div>
                                                        </div>

                                                        @if($plan)
                                                        <div class="text-[10px] px-1 py-0.5 bg-gray-100 rounded text-gray-700">{{ $plan }}u</div>
                                                        @endif
                                                    </div>
                                                </div>
                                                @endforeach
                                            </div>
                                            </td>
                                        @endfor
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div x-show="hoverVisible" x-cloak style="position: fixed; left: 0; top: 0; z-index: 60;" x-bind:style="'left: ' + (hoverX + 12) + 'px; top: ' + (hoverY + 12) + 'px; max-width: 360px;'" class="pointer-events-none">
        <div class="pointer-events-auto bg-white border border-gray-200 rounded shadow-lg p-3 text-sm w-full">
            <div class="font-semibold text-sm" x-text="hoverPayload?.head ?? ''"></div>
            <div class="text-xs text-gray-600 mt-1" x-text="hoverPayload?.sub ?? ''"></div>
            <div class="mt-2 grid grid-cols-2 gap-2 text-xs text-gray-700">
                <div>ETD: <span x-text="hoverPayload?.etd ?? '-'"></span></div>
                <div>ETA: <span x-text="hoverPayload?.eta ?? '-'"></span></div>
                <div>ATD: <span x-text="hoverPayload?.atd ?? '-'"></span></div>
                <div>ATA: <span x-text="hoverPayload?.ata ?? '-'"></span></div>
                <div>Plan: <span x-text="hoverPayload?.plan ?? 0"></span> unit</div>
                <div x-show="hoverPayload?.lead_time" x-cloak>Durasi: <span x-text="hoverPayload?.lead_time"></span> hari</div>
            </div>
        </div>
    </div>

    <div x-show="showModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-black/40" x-on:click="closeModal()"></div>
        <div class="relative w-full max-w-lg bg-white rounded-lg border border-gray-200 p-4">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <div class="text-sm font-semibold" x-text="modalPayload?.head"></div>
                    <div class="text-xs text-gray-500 mt-1" x-text="modalPayload?.sub"></div>
                </div>
                <button type="button" class="text-gray-500 hover:text-gray-700" x-on:click="closeModal()">✕</button>
            </div>

            <div class="mt-3 text-sm text-gray-700 grid grid-cols-2 gap-2">
                <div>ETD: <span x-text="modalPayload?.etd ?? '-'"></span></div>
                <div>ETA: <span x-text="modalPayload?.eta ?? '-'"></span></div>
                <div>ATD: <span x-text="modalPayload?.atd ?? '-'"></span></div>
                <div>ATA: <span x-text="modalPayload?.ata ?? '-'"></span></div>
                <div>Plan: <span x-text="modalPayload?.plan ?? 0"></span> unit</div>
                <div x-show="modalPayload?.lead_time" x-cloak>Durasi sailing: <span x-text="modalPayload?.lead_time"></span> hari</div>
            </div>

            <div class="mt-4 text-right">
                <button type="button" class="px-3 py-1.5 bg-blue-600 text-white rounded" x-on:click="closeModal()">Tutup</button>
            </div>
        </div>
    </div>

    <style>
        :root {
            --row-height: 96px;
            --header-height: 64px;
        }

        .calendar-bar {
            min-height: 36px;
            display: flex;
            align-items: center;
            gap: 8px;
            overflow: visible;
            padding-left: 10px;
            padding-right: 10px;
            white-space: nowrap;
            min-width: 120px;
            box-sizing: border-box;
        }

        .bars-overlay {
            position: absolute;
            pointer-events: none;
            inset: 0;
            z-index: 10;
        }

        .chip {
            min-width: 120px;
            max-width: 100%;
            padding-left: 8px;
            padding-right: 8px;
            white-space: nowrap;
        }

        .chip:hover {
            transform: translateY(-2px) scale(1.02);
            z-index: 30;
        }

        .chip-color-indicator {
            width: 8px;
            height: 32px;
            border-radius: 3px;
        }

        .calendar-bar.jss-focused {
            outline: 2px solid rgba(59, 130, 246, 0.9);
            transform: scale(1.02);
            z-index: 9999 !important;
        }

        .chip .truncate { max-width: 180px; }
    </style>

    <script>
        function calendarComponent() {
            return {
                hoverVisible: false,
                hoverPayload: null,
                hoverX: 0,
                hoverY: 0,
                showModal: false,
                modalPayload: null,
                init() {
                    this.assignColors();
                    this.$nextTick(() => {
                        this.syncBars();
                        setTimeout(() => {
                            this.assignColors();
                            this.syncBars();
                        }, 500);
                    });
                    const scroller = document.getElementById('calendar-scroll-{{ md5($d['month_label'] ?? '') }}');
                    if (scroller) {
                        scroller.addEventListener('scroll', () => requestAnimationFrame(() => this.syncBars()));
                        window.addEventListener('resize', () => requestAnimationFrame(() => this.syncBars()));
                    }
                },
                openModal(payload) {
                    this.modalPayload = payload;
                    this.showModal = true;
                },
                closeModal() {
                    this.showModal = false;
                    this.modalPayload = null;
                },
                showHover(e, payload) {
                    this.hoverPayload = payload;
                    this.hoverX = e.clientX;
                    this.hoverY = e.clientY;
                    this.hoverVisible = true;
                },
                hideHover() {
                    this.hoverVisible = false;
                    this.hoverPayload = null;
                },
                syncBars() {
                    this.$nextTick(() => {
                        const scroller = document.getElementById('calendar-scroll-{{ md5($d['month_label'] ?? '') }}');
                        if (!scroller) return;
                        let overlay = scroller.querySelector('.bars-overlay');
                        const table = scroller.querySelector('table');
                        if (!table) return;
                        if (!overlay) {
                            overlay = document.createElement('div');
                            overlay.className = 'bars-overlay absolute inset-0 pointer-events-none z-10';
                            scroller.appendChild(overlay);
                        }
                        const tbody = table.querySelector('tbody');
                        if (!tbody) return;
                        const rows = Array.from(tbody.querySelectorAll('tr[data-lane-index]'));
                        const bars = Array.from(scroller.querySelectorAll('.calendar-bar'));
                        const header = table.querySelector('thead');
                        const headerHeight = header ? header.offsetHeight : 0;
                        const scrollTop = scroller.scrollTop || 0;

                        const indexMap = {};
                        bars.forEach(bar => {
                            const laneIdx = bar.getAttribute('data-bar-lane-index') || '0';
                            const day = bar.getAttribute('data-chip-day') || '0';
                            const key = `${laneIdx}|${day}`;
                            if (!indexMap[key]) indexMap[key] = 0;
                            bar.dataset.stackIndex = indexMap[key]++;
                        });

                        bars.forEach(bar => {
                            const laneIdx = parseInt(bar.getAttribute('data-bar-lane-index') || '0', 10);
                            const laneRow = rows.find(r => parseInt(r.getAttribute('data-lane-index') || '-1', 10) === laneIdx);
                            if (!laneRow) {
                                bar.style.transform = `translateY(${headerHeight + 8}px)`;
                                return;
                            }
                            const rowTop = laneRow.offsetTop;
                            const desiredCenter = rowTop + (laneRow.offsetHeight / 2);
                            let translateY = desiredCenter - scrollTop;
                            translateY = translateY + (headerHeight / 2) - (bar.offsetHeight / 2);

                            const idx = parseInt(bar.dataset.stackIndex || '0', 10);
                            const spacing = 6;
                            translateY += idx * (bar.offsetHeight + spacing);

                            if (translateY < headerHeight + 6) translateY = headerHeight + 6;
                            bar.style.transform = `translateY(${Math.round(translateY)}px)`;
                        });
                    });
                },
                colorFromString(s) {
                    if (!s) s = '' + Math.random();
                    let h = 0;
                    for (let i = 0; i < s.length; i++) {
                        h = (h << 5) - h + s.charCodeAt(i);
                        h |= 0;
                    }
                    h = Math.abs(h) % 360;
                    return `hsl(${h} 68% 48%)`;
                },
                assignColors() {
                    const chips = Array.from(document.querySelectorAll('.chip'));
                    chips.forEach(c => {
                        const code = c.getAttribute('data-vessel-key') || c.getAttribute('data-chip-id') || '';
                        const color = this.colorFromString(code);
                        const ind = c.querySelector('.chip-color-indicator');
                        if (ind) ind.style.background = color;
                        c.style.borderLeft = `4px solid ${color}`;
                    });
                    const bars = Array.from(document.querySelectorAll('.calendar-bar'));
                    bars.forEach(b => {
                        const code = b.getAttribute('data-vessel-key') || b.getAttribute('data-chip-id') || '';
                        const color = this.colorFromString(code);
                        b.style.borderLeft = `4px solid ${color}`;
                        b.style.background = b.style.background || 'rgba(255,255,255,0.98)';
                        const spans = b.querySelectorAll('span');
                        spans.forEach(s => s.style.textTransform = 'uppercase');
                    });
                }
            };
        }
    </script>
</div>
