<x-filament::widget>
 <x-filament::card>
 <style>
 .gantt-wrap {
 border: 1px solid #e5e7eb;
 border-radius: 8px;
 overflow: auto;
 background: #fff
 }
 @media (prefers-color-scheme: dark) {
 .gantt-wrap {
 border-color: #374151;
 background: #111827
 }
 }

 .gantt-grid {
 display: grid;
 grid-template-columns: 160px 1fr;
 min-width: 900px
 }

 .gantt-days {
 display: grid;
 grid-template-columns: repeat(31, 1fr);
 background: #f9fafb;
 border-bottom: 1px solid #e5e7eb;
 position: sticky;
 top: 0;
 z-index: 1
 }
 @media (prefers-color-scheme: dark) {
 .gantt-days {
 background: #1f2937;
 border-bottom-color: #374151
 }
 }

 .gantt-day {
 font-size: .7rem;
 padding: 6px 0;
 text-align: center;
 border-right: 1px solid #f3f4f6
 }
 @media (prefers-color-scheme: dark) {
 .gantt-day {
 border-right-color: #374151;
 color: #d1d5db
 }
 }

 .gantt-day.w {
 background: #fff7ed
 }
 @media (prefers-color-scheme: dark) {
 .gantt-day.w {
 background: #451a03
 }
 }

 .gantt-label {
 padding: 10px;
 font-size: .8rem;
 border-right: 1px solid #e5e7eb;
 background: #fff;
 position: sticky;
 left: 0;
 z-index: 1;
 border-bottom: 1px solid #f3f4f6
 }
 @media (prefers-color-scheme: dark) {
 .gantt-label {
 border-right-color: #374151;
 background: #111827;
 border-bottom-color: #374151;
 color: #e5e7eb
 }
 }

 .gantt-row {
 display: grid;
 grid-template-columns: repeat(31, 1fr);
 border-bottom: 1px solid #f3f4f6;
 min-height: 44px;
 align-items: center;
 position: relative
 }
 @media (prefers-color-scheme: dark) {
 .gantt-row {
 border-bottom-color: #374151
 }
 }

 .gantt-bar {
 position: absolute;
 top: 8px;
 height: 26px;
 border-radius: 6px;
 background: linear-gradient(135deg, #3b82f6, #1e40af);
 color: #fff;
 display: flex;
 align-items: center;
 padding: 0 8px;
 font-size: .7rem;
 
 white-space: nowrap;
 overflow: hidden;
 text-overflow: ellipsis
 }

 .legend-dot {
 width: 12px;
 height: 12px;
 border-radius: 3px
 }
 </style>

 <div class="mb-2 text-sm text-gray-700 dark:text-slate-300"><x-heroicon-o-calendar class="w-4 h-4 inline" /> Timeline Jadwal — Oktober 2025 (Mock)</div>

 <div class="gantt-wrap overflow-x-auto">
 <div class="gantt-grid">
 {{-- Header kiri --}}
 <div class="gantt-label font-semibold">Kapal &amp; Voyage</div>

 {{-- Header hari --}}
 <div class="gantt-days">
 @for ($i = 1; $i <= 31; $i++)
 <div class="gantt-day {{ in_array($i % 7, [0, 6]) ? 'w' : '' }}">{{ $i }}</div>
 @endfor
 </div>

 {{-- Row 1 --}}
 <div class="gantt-label">TTSA 151</div>
 <div class="gantt-row">
 <div class="gantt-bar" title="{{ $bars[0]['title'] }}"
 style="left: calc(({{ $bars[0]['start'] - 1 }} / 31) * 100% + 4px);
 width: calc(({{ $bars[0]['end'] - $bars[0]['start'] + 1 }} / 31) * 100% - 8px);">
 {{ $bars[0]['label'] }}
 </div>
 </div>

 {{-- Row 2 --}}
 <div class="gantt-label">MRMA 182</div>
 <div class="gantt-row">
 <div class="gantt-bar" title="{{ $bars[1]['title'] }}"
 style="left: calc(({{ $bars[1]['start'] - 1 }} / 31) * 100% + 4px);
 width: calc(({{ $bars[1]['end'] - $bars[1]['start'] + 1 }} / 31) * 100% - 8px);">
 {{ $bars[1]['label'] }}
 </div>
 </div>

 {{-- Row 3 --}}
 <div class="gantt-label">TTJ 301</div>
 <div class="gantt-row">
 <div class="gantt-bar" title="{{ $bars[2]['title'] }}"
 style="left: calc(({{ $bars[2]['start'] - 1 }} / 31) * 100% + 4px);
 width: calc(({{ $bars[2]['end'] - $bars[2]['start'] + 1 }} / 31) * 100% - 8px);">
 {{ $bars[2]['label'] }}
 </div>
 </div>
 </div>
 </div>

 <div class="mt-3 text-xs text-gray-600 dark:text-slate-400 flex flex-wrap items-center gap-4">
 <span class="inline-flex items-center gap-2">
 <i class="legend-dot" style="background:linear-gradient(135deg,#3b82f6,#1e40af)"></i> Final
 </span>
 <span class="inline-flex items-center gap-2">
 <i class="legend-dot" style="background:#f59e0b"></i> Feedback
 </span>
 <span class="inline-flex items-center gap-2">
 <i class="legend-dot" style="background:#9ca3af"></i> Draft
 </span>
 <span class="text-gray-500 dark:text-slate-400">• Perjalanan (ETD → ETA). Mock tampilan, belum terhubung data.</span>
 </div>
 </x-filament::card>
</x-filament::widget>
