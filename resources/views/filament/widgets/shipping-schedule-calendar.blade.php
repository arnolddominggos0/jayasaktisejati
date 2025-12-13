@php
$d = $this->getData();
$completion = $d['kpi']['completion'] ?? 0;
@endphp

<div class="space-y-4" x-data="calendarComponent()" x-init="init()">
  <!-- header omitted for brevity (keep your existing header) -->

  {{-- Calendar / table --}}
  <div class="bg-white rounded-xl shadow-sm border overflow-hidden">
    <div class="relative">
      <div class="overflow-x-auto scrollbar-thin relative" id="calendar-scroll-{{ md5($d['month_label'] ?? '') }}">
        <div class="bars-overlay absolute inset-0 pointer-events-none z-10"></div>

        @php
        $daysCount = $d['days_count'] ?? 30;
        $lanes = array_keys($d['lanes'] ?? []);
        $laneIndexMap = [];
        foreach ($lanes as $idx => $k) { $laneIndexMap[$k] = $idx; }
        $overrides = config('vessel_code.overrides', config('vessel.overrides', []));
        @endphp

        <div class="min-w-[1200px]">
          <div class="max-h-[540px] overflow-y-auto">
            <table class="w-full border-collapse text-[10px] table-fixed">
              <thead class="sticky top-0 z-30 bg-white shadow-sm">
                <tr>
                  <th class="sticky left-0 z-40 bg-gray-100 border-b border-r px-2 py-2 text-left w-44 min-w-[11rem]">
                    <div class="text-[10px] font-semibold text-gray-900">Rute (POL → POD)</div>
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

                    <th class="border-b border-gray-300 text-center px-1 py-1 {{ $weekendClass }} {{ $todayRing }} w-14 min-w-[3.6rem]" title="{{ $day['date'] ?? '' }}">
                      <div class="text-[8.5px] font-semibold uppercase tracking-wide {{ $dowClass }}">{{ $day['dow'] ?? '' }}</div>
                      <div class="text-sm font-bold mt-0.5 {{ $numClass }}" style="font-size:12px;">{{ $day['n'] ?? '' }}</div>
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
                    <td class="sticky left-0 z-40 border-b border-r px-3 py-2.5 {{ $laneBg }} w-44 min-w-[11rem]">
                      <div class="flex flex-col gap-0.5">
                        <span class="text-[10px] font-semibold">{{ $label }}</span>
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
                        $chips = $d['bucket'][$key][$i] ?? [];
                        $hasSailing = !empty($d['sailing_bg'][$i]);
                      @endphp

                      <td class="border-b border-gray-200 align-top relative w-14 min-w-[3.6rem]">
                        @if ($hasSailing)
                          <div class="absolute inset-0 bg-gradient-to-br from-blue-50 via-sky-50 to-indigo-50 opacity-60 pointer-events-none"></div>
                        @endif

                        <div class="relative z-10 px-1 py-1 space-y-0.5 min-h-[36px] max-h-[240px]">
                          @if (empty($chips))
                            <div class="text-[9px] text-gray-400 text-center py-2">—</div>
                          @endif

                          <div class="cell-chips">
                            @foreach ($chips as $chip)
                              @php
                                $meta = $chip['meta'] ?? [];
                                if (is_object($meta)) $meta = (array) $meta;

                                $chipId = $meta['id'] ?? ($chip['id'] ?? uniqid('chip_'));
                                $voyageNo = $meta['voyage_no'] ?? ($chip['voyage_no'] ?? null);
                                $vesselName = $meta['vessel_name'] ?? '';
                                $vesselCodeMeta = $meta['vessel_code'] ?? '';
                                $short = $chip['short'] ?? ($chip['label'] ?? '');
                                // compute shortDisplay as before
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
                                $payload = json_encode([
                                  'id' => $chipId,
                                  'short' => $shortDisplay,
                                  'voyage_no' => $voyageNo,
                                  'label' => $label,
                                  'plan' => $plan,
                                  'head' => $chip['head'] ?? ($meta['head'] ?? $label),
                                  'sub' => $chip['sub'] ?? ($meta['sub'] ?? ($plan ? ($plan . ' unit') : '')),
                                ], JSON_HEX_APOS|JSON_HEX_QUOT);
                                $vesselKey = $shortDisplay;
                              @endphp

                              <div
                                x-on:mouseenter="(e) => showHover(e, {!! $payload !!})"
                                x-on:mouseleave="hideHover()"
                                x-on:click="$dispatch('jss-show-chip', {!! $payload !!}); openModal({!! $payload !!})"
                                role="button"
                                tabindex="0"
                                class="chip compact-chip {{ $chip['class'] ?? ($meta['class'] ?? 'bg-white text-gray-800 border border-gray-200') }}"
                                data-chip-id="{{ $chipId }}"
                                data-vessel-key="{{ $vesselKey }}"
                                title="{{ $label }}">
                                <div class="chip-left" aria-hidden="true"><div class="chip-color-indicator"></div></div>
                                <div class="flex-1 min-w-0">
                                  <div class="chip-label truncate">{{ $shortDisplay }}{{ $voyageNo ? '/' . $voyageNo : '' }}</div>
                                </div>
                              </div>

                            @endforeach
                          </div>
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

  {{-- Hover tooltip & modal (keep your existing) --}}
</div>

<style>
  :root { --row-height: 96px; --header-height: 64px; }
  .bars-overlay { position:absolute; pointer-events:none; inset:0; z-index:10; }

  /* stack chips vertically so they never overflow column width */
  .cell-chips { display:flex; flex-direction:column; gap:6px; align-items:stretch; }

  /* compact single-line chip */
  .compact-chip {
    width:100%;
    box-sizing:border-box;
    min-height:26px;
    padding:4px 6px;
    display:flex;
    align-items:center;
    gap:8px;
    border-radius:6px;
    background:#fff;
    border:1px solid rgba(0,0,0,0.06);
    box-shadow:0 1px 0 rgba(0,0,0,0.03);
    overflow:hidden;
    white-space:nowrap;
    text-overflow:ellipsis;
    font-size:10px;
    line-height:1;
  }
  .compact-chip:hover { transform: translateY(-1px); z-index:30; }

  .chip-left { width:8px; display:flex; align-items:center; justify-content:center; padding-right:4px; }
  .chip-color-indicator { width:6px; height:14px; border-radius:3px; flex-shrink:0; }

  .compact-chip .chip-label { font-weight:600; font-size:10px; letter-spacing:0.2px; color:#0f172a; }
  thead th { font-size:10px; padding:6px 4px; }
  tbody td { padding:6px 4px; vertical-align:top; min-height:36px; }
</style>

<script>
function calendarComponent() {
  return {
    hoverVisible:false, hoverPayload:null, hoverX:0, hoverY:0, showModal:false, modalPayload:null,
    init() {
      this.assignColors();
      this.$nextTick(()=> setTimeout(()=> this.assignColors(), 200));
      const scroller = document.getElementById('calendar-scroll-{{ md5($d['month_label'] ?? '') }}');
      if (scroller) {
        scroller.addEventListener('scroll', ()=> requestAnimationFrame(()=> this.assignColors()));
        window.addEventListener('resize', ()=> requestAnimationFrame(()=> this.assignColors()));
      }
    },
    openModal(payload){ this.modalPayload = payload; this.showModal = true; },
    closeModal(){ this.showModal = false; this.modalPayload = null; },
    showHover(e,payload){ this.hoverPayload = payload; this.hoverX = e.clientX; this.hoverY = e.clientY; this.hoverVisible = true; },
    hideHover(){ this.hoverVisible = false; this.hoverPayload = null; },
    colorFromString(s){ if(!s) s = ''+Math.random(); let h=0; for(let i=0;i<s.length;i++){ h=(h<<5)-h+s.charCodeAt(i); h|=0; } h=Math.abs(h)%360; return `hsl(${h} 68% 48%)`; },
    assignColors(){
      const chips = Array.from(document.querySelectorAll('.compact-chip'));
      chips.forEach(c=>{
        const code = c.getAttribute('data-vessel-key')||c.getAttribute('data-chip-id')||'';
        const color = this.colorFromString(code);
        const ind = c.querySelector('.chip-color-indicator');
        if(ind) ind.style.background = color;
        if(!c.style.borderLeft) c.style.borderLeft = `4px solid ${color}`;
      });
    }
  }
}
</script>
