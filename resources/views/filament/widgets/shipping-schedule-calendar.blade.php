@php($d = $this->getData())
@php($items = $d['items'] ?? [])
@php($days = $d['days'] ?? [])

<div class="mx-[-1rem] sm:mx-[-2rem]">
    <div class="px-4 sm:px-8 space-y-4">
        <div class="flex items-center justify-between">
            <div class="text-xl font-semibold">Timeline Jadwal Kapal — {{ $d['month_label'] ?? '' }}</div>
            <div class="flex items-center gap-2">
                <a href="?month={{ $d['prev'] ?? now()->subMonth()->format('Y-m') }}"
                    class="fi-btn fi-btn-size-md fi-btn-color-gray">Bulan Sebelumnya</a>
                <a href="?month={{ now()->format('Y-m') }}" class="fi-btn fi-btn-size-md fi-btn-color-gray">Bulan Ini</a>
                <a href="?month={{ $d['next'] ?? now()->addMonth()->format('Y-m') }}"
                    class="fi-btn fi-btn-size-md fi-btn-color-primary">Bulan Berikutnya</a>
            </div>
        </div>

        <div class="rounded-xl border bg-white overflow-hidden">
            <div class="overflow-x-auto">
                <div class="relative w-full"
                    style="--first: {{ $d['first_w'] ?? 360 }}px;
                            --days:  {{ $d['days_count'] ?? 0 }};
                            --rowh:  {{ $d['row_h'] ?? 64 }}px;
                            --colmin: {{ $d['col_min'] ?? 36 }}px;
                            --col: max(var(--colmin), calc((100% - var(--first)) / var(--days)));
                            width: calc(var(--first) + var(--days) * var(--col));">

                    <div class="grid text-sm font-medium sticky top-0 z-30 bg-white"
                        style="grid-template-columns: var(--first) repeat(var(--days), var(--col));">
                        <div class="px-4 py-3 border-b sticky left-0 z-30 bg-white">Kapal • Voyage • Info</div>
                        @foreach ($days as $day)
                            <div
                                class="text-center border-b border-l {{ $day['isWeekend'] ? 'bg-gray-50' : 'bg-white' }}">
                                <div class="py-3 leading-none">{{ $day['n'] }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if (($d['today_idx'] ?? null) !== null)
                        <div class="absolute z-20"
                            style="top: 48px; bottom: 0;
                                    left: calc(var(--first) + ({{ $d['today_idx'] - 1 }} * var(--col)));
                                    width: 2px; background: rgba(37,99,235,.65);">
                        </div>
                    @endif

                    <div class="max-h-[520px] overflow-y-auto">
                        <div class="divide-y">
                            @forelse($items as $row)
                                <div class="grid relative"
                                    style="grid-template-columns: var(--first) repeat(var(--days), var(--col));
                                            height: var(--rowh);">
                                    <div class="px-4 py-3 sticky left-0 z-10 bg-white">
                                        <div class="font-medium leading-5 truncate">{{ $row['title'] }}</div>
                                        <div class="text-[12px] text-gray-500 leading-4 truncate">{{ $row['sub'] }}
                                        </div>
                                    </div>

                                    <div class="col-span-[var(--days)] relative">
                                        <div class="absolute inset-0 pointer-events-none"
                                            style="background-image:
                                                 repeating-linear-gradient(to right,
                                                     rgba(0,0,0,0.06) 0,
                                                     rgba(0,0,0,0.06) 1px,
                                                     transparent 1px,
                                                     transparent var(--col));">
                                        </div>

                                        @foreach ($days as $idx => $day)
                                            @if ($day['isWeekend'])
                                                <div class="absolute inset-y-0 bg-gray-50"
                                                    style="left: calc({{ $idx }} * var(--col)); width: var(--col);">
                                                </div>
                                            @endif
                                        @endforeach

                                        <div class="absolute h-8 flex items-center px-2 text-white text-xs rounded-md shadow"
                                            title="{{ $row['sub'] }}"
                                            style="top: calc(50% - 16px);
                                                    left: calc(({{ $row['start'] - 1 }} * var(--col)));
                                                    width: calc({{ $row['length'] }} * var(--col));
                                                    background: linear-gradient(90deg, {{ $row['c1'] }} 0%, {{ $row['c2'] }} 100%);">
                                            {{ $row['badge'] ?? 'final' }}
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="p-6 text-sm text-gray-500">Tidak ada jadwal pada bulan ini.</div>
                            @endforelse
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <div class="flex items-center gap-4 text-xs">
            <div class="flex items-center gap-2">
                <div class="h-3 w-3 rounded-sm" style="background: linear-gradient(90deg, #2563eb 0%, #1d4ed8 100%);">
                </div>
                <span>Final</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="h-3 w-[2px] bg-blue-600"></div>
                <span>Hari ini</span>
            </div>
        </div>
    </div>
</div>
