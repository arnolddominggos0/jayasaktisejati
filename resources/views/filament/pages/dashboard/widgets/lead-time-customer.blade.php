<x-filament::section class="bg-white dark:bg-slate-900">
 <x-slot name="heading">Lead Time Evaluation (Sea) per Customer</x-slot>

 {{-- Filter --}}
 <div class="mb-4">
 <form wire:submit.prevent="applyForm">
 {{ $this->form }}
 </form>
 </div>

 @php
 $cards = [
 ['key' => 'dwelling', 'title' => 'Dwelling time'],
 ['key' => 'sailing', 'title' => 'Sailing time'],
 ['key' => 'dooring', 'title' => 'Dooring time'],
 ['key' => 'total', 'title' => 'Total L/T'],
 ];
 @endphp

 <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
 @foreach ($cards as $c)
 @php
 $ok = (int) ($stats[$c['key']]['ok'] ?? 0);
 $ng = (int) ($stats[$c['key']]['ng'] ?? 0);
 $sum = $ok + $ng;
 $okPct = $sum ? round($ok / $sum * 100) : 0;
 $ngPct = $sum ? 100 - $okPct : 0;
 @endphp

 <div class="p-4 rounded-xl border border-gray-100 dark:border-slate-800">
 <div class="text-sm font-medium mb-2">{{ $c['title'] }}</div>

 @if ($sum === 0)
 <div class="h-48 flex items-center justify-center text-sm text-gray-500 dark:text-slate-400">
 Tidak ada data pada rentang ini
 </div>
 @else
 <div class="h-48" wire:ignore
 x-data="ltDonut({ ok: {{ $ok }}, ng: {{ $ng }} })"
 x-init="init()"
 x-on:resize.window.debounce.200ms="redraw()">
 <canvas x-ref="cv"></canvas>
 </div>
 @endif

 <div class="flex items-center justify-center gap-4 mt-3 text-xs">
 <span class="inline-flex items-center gap-1">
 <span class="h-2 w-2 rounded-full" style="background:#22C55E"></span>
 OK: {{ $ok }} ({{ $okPct }}%)
 </span>
 <span class="inline-flex items-center gap-1">
 <span class="h-2 w-2 rounded-full" style="background:#EF4444"></span>
 NG: {{ $ng }} ({{ $ngPct }}%)
 </span>
 </div>
 </div>
 @endforeach
 </div>

 @script
 <script>
 document.addEventListener('alpine:init', () => {
 Alpine.data('ltDonut', (cfg) => ({
 chart: null,
 init() {
 this.$nextTick(() => this.redraw());
 },
 redraw() {
 const ctx = this.$refs.cv?.getContext('2d');
 if (!ctx || typeof Chart === 'undefined') return;
 if (this.chart) this.chart.destroy();

 this.chart = new Chart(ctx, {
 type: 'doughnut',
 data: {
 labels: ['OK','NG'],
 datasets: [{
 data: [cfg.ok, cfg.ng],
 backgroundColor: ['#22C55E','#EF4444'],
 borderWidth: 0,
 }],
 },
 options: {
 maintainAspectRatio: false,
 cutout: '62%',
 plugins: {
 legend: { display: false },
 tooltip: {
 displayColors: false,
 backgroundColor: document.documentElement.classList.contains('dark') ? 'rgba(15,23,42,0.95)' : 'rgba(0,0,0,0.8)',
 titleColor: document.documentElement.classList.contains('dark') ? '#f8fafc' : '#fff',
 bodyColor: document.documentElement.classList.contains('dark') ? '#cbd5e1' : '#fff',
 borderColor: document.documentElement.classList.contains('dark') ? 'rgba(255,255,255,0.08)' : 'transparent',
 borderWidth: document.documentElement.classList.contains('dark') ? 1 : 0,
 padding: 10,
 cornerRadius: 8,
 },
 },
 },
 });
 },
 destroy() { if (this.chart) this.chart.destroy(); },
 }));
 });
 </script>
 @endscript
</x-filament::section>
