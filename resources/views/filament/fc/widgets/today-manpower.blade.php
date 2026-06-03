@php
    $data = $this->getViewData();
    $items = $data['items'];
    $totalPresent = $data['totalPresent'];
    $totalFit = $data['totalFit'];
    $totalUnfit = $data['totalUnfit'];
    $totalPending = $data['totalPending'];
    $totalAbsent = $data['totalAbsent'];
@endphp

<x-filament::section>
    <x-slot name="heading">
        <div class="flex items-center gap-2">
            <x-heroicon-o-users class="h-5 w-5" />
            {{ __('Daftar MP Hari Ini') }}
        </div>
    </x-slot>

    <x-slot name="headerEnd">
        @if($totalPresent > 0)
            <div class="flex items-center gap-3 text-sm">
                <span class="inline-flex items-center gap-1">
                    <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                    <span class="text-gray-600 dark:text-gray-400">Siap Kerja: <span class="font-semibold text-emerald-700 dark:text-emerald-400">{{ $totalFit }}</span></span>
                </span>
                @if($totalUnfit > 0)
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full bg-red-500"></span>
                        <span class="text-red-700 dark:text-red-400">Tidak Fit: <span class="font-semibold">{{ $totalUnfit }}</span></span>
                    </span>
                @endif
                @if($totalPending > 0)
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full bg-amber-500"></span>
                        <span class="text-amber-700 dark:text-amber-400">Belum Diperiksa: <span class="font-semibold">{{ $totalPending }}</span></span>
                    </span>
                @endif
                @if($totalAbsent > 0)
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full bg-gray-400"></span>
                        <span class="text-gray-500 dark:text-gray-400">Tidak Hadir: <span class="font-semibold">{{ $totalAbsent }}</span></span>
                    </span>
                @endif
            </div>
        @endif
    </x-slot>

    <div class="space-y-2">
        @forelse($items as $it)
            @php
                $fms = $it['final_mp_status'] ?? 'Belum Dinilai';

                $borderColor = match($fms) {
                    'Siap Kerja'              => 'border-l-emerald-500',
                    'Perlu Pemeriksaan Ulang' => 'border-l-amber-500',
                    'Tidak Fit'               => 'border-l-red-500',
                    'APD Tidak Lengkap'       => 'border-l-orange-500',
                    'Istirahat 30 Menit'      => 'border-l-blue-400',
                    'Belum Dinilai'           => 'border-l-gray-300',
                    'Tidak Hadir'             => 'border-l-gray-300',
                    default                   => 'border-l-gray-300',
                };

                $statusBadge = match($fms) {
                    'Siap Kerja'              => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400',
                    'Perlu Pemeriksaan Ulang' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400',
                    'Tidak Fit'               => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400',
                    'APD Tidak Lengkap'       => 'bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-400',
                    'Istirahat 30 Menit'      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400',
                    'Belum Dinilai'           => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                    'Tidak Hadir'             => 'bg-gray-100 text-gray-500 dark:bg-gray-800 dark:text-gray-400',
                    default                   => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400',
                };
            @endphp
            <div class="rounded-lg border border-l-4 {{ $borderColor }} {{ ($it['is_backup'] ?? false) ? 'bg-indigo-50/50 dark:bg-indigo-950/20' : 'bg-white dark:bg-gray-900' }} p-2.5 dark:border-gray-700 flex items-center justify-between gap-3">
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <span class="font-medium text-sm text-gray-900 dark:text-white truncate">{{ $it['name'] }}</span>
                        @if($it['is_backup'] ?? false)
                            <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-semibold rounded bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-400">Backup MP</span>
                        @endif
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ strtoupper($it['role']) }}</div>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full {{ $statusBadge }}">
                        {{ $fms }}
                    </span>
                    <span class="text-xs text-gray-400 tabular-nums">{{ $it['time'] ?? '-' }}</span>
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center py-8 text-gray-400 dark:text-gray-500">
                <x-heroicon-o-user-group class="h-10 w-10 mb-2 opacity-50" />
                <div class="text-sm">Belum ada data kehadiran hari ini.</div>
            </div>
        @endforelse
    </div>
</x-filament::section>