<div class="col-span-3">
    <x-filament::section
        class="w-full rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm bg-white dark:bg-gray-900">
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Aktivitas Terbaru</span>
                <a class="text-sm text-primary-600 hover:underline"
                    href="{{ route('filament.admin.resources.shipments.index') }}">
                    Lihat semua
                </a>
            </div>
        </x-slot>

        <div class="max-h-96 overflow-y-auto">
            @forelse ($groups as $group)
                <div class="px-4 pt-3 pb-2 sticky top-0 bg-white/90 dark:bg-gray-900/90 backdrop-blur z-10">
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 tracking-wide">
                        {{ $group['title'] }}
                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    @foreach ($group['items'] as $it)
                        <div class="flex items-start gap-3 py-2.5 px-4 hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <span class="mt-2 h-2 w-2 rounded-full {{ $it['dotClass'] }}"></span>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="{{ $it['editUrl'] }}" target="_blank" rel="noopener"
                                        class="font-mono text-xs px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/60">
                                        {{ $it['code'] }}
                                    </a>

                                    <x-filament::icon icon="{{ $it['icon'] }}" class="h-4 w-4 text-gray-500" />

                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        {{ $it['eventLabel'] }} oleh
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1.5 pl-1.5 pr-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700">
                                        <span
                                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-[10px] text-gray-800 dark:text-gray-200">
                                            {{ $it['initial'] }}
                                        </span>
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $it['user'] }}</span>
                                    </span>

                                    @if ($it['showStatus'])
                                        <span
                                            class="text-[11px] ml-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full {{ $it['chipClass'] }}">
                                            {{ $it['toLabel'] }}
                                        </span>

                                        @if ($it['fromLabel'] && in_array($it['event'], ['status_changed', 'cancelled', 'uncancelled'], true))
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                (dari {{ $it['fromLabel'] }})
                                            </span>
                                        @endif
                                    @endif

                                    @if ($it['changedText'])
                                        <span
                                            class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                                            Field: {{ $it['changedText'] }}
                                        </span>
                                    @endif
                                </div>

                                <time title="{{ $it['fullTime'] }}"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                    {{ $it['calendarTime'] }}
                                </time>
                            </div>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="py-3 px-4 text-sm text-gray-500">Belum ada aktivitas.</div>
            @endforelse
        </div>
    </x-filament::section>
</div>
