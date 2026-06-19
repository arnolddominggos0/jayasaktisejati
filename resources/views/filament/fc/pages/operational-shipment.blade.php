<x-filament-panels::page>
    @php $handoverWaiting = $this->getHandoverWaitingCount(); @endphp

    @if ($handoverWaiting > 0)
        <div class="mb-4 flex items-start gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-900/50 dark:bg-amber-900/20">
            <x-heroicon-o-exclamation-triangle class="mt-0.5 h-5 w-5 shrink-0 text-amber-500 dark:text-amber-400" />
            <div>
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300">
                    Inspeksi handover belum selesai
                </p>
                <p class="mt-0.5 text-sm text-amber-700 dark:text-amber-400">
                    {{ $handoverWaiting }} unit menunggu inspeksi. Selesaikan inspeksi setiap unit sebelum proses stuffing.
                </p>
            </div>
        </div>
    @endif

    {{ $this->infolist }}
</x-filament-panels::page>
