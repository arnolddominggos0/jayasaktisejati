<div x-data="{
    draggingId: null,
    startDrag(id) { this.draggingId = id },
    drop(date) {
        if (this.draggingId) {
            $wire.rescheduleVoyage(this.draggingId, date)
            this.draggingId = null
        }
    }
}" class="bg-white rounded-2xl border overflow-hidden">

    <div class="px-4 py-3 border-b font-semibold text-sm flex justify-between">
        <span>
            Kalender Jadwal Pelayaran — {{ $calendar['month_label'] }}
        </span>
        <span class="text-xs text-gray-500">
            Drag untuk reschedule ETD
        </span>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-[1400px] w-full border-collapse text-[11px]">

            <thead>
                <tr>
                    <th class="sticky left-0 bg-gray-100 border px-3 py-2 w-44 text-left z-10">
                        Lane
                    </th>

                    @foreach ($calendar['days'] as $day)
                        <th @dragover.prevent @drop="drop('{{ $day['date'] }}')"
                            class="border px-1 py-2 text-center w-12
                            {{ $day['isWeekend'] ? 'bg-rose-50 text-rose-600' : 'bg-gray-50' }}
                            {{ $day['date'] === now()->toDateString() ? 'ring-2 ring-blue-500' : '' }}">

                            <div class="text-[9px] uppercase tracking-wide">
                                {{ $day['dow'] }}
                            </div>
                            <div class="font-semibold">
                                {{ $day['n'] }}
                            </div>
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>

                @foreach ($calendar['lanes'] as $laneKey => $laneLabel)

                    @php
                        $isActual = str_contains($laneKey, 'act');
                    @endphp

                    <tr>
                        <td class="sticky left-0 bg-white border px-3 py-2 font-semibold z-10">
                            {{ $laneLabel }}
                        </td>

                        <td colspan="{{ $calendar['days_count'] }}" class="p-0">

                            <div class="relative grid"
                                style="grid-template-columns: repeat({{ $calendar['days_count'] }}, minmax(48px,1fr));">

                                @foreach ($calendar['bars'][$laneKey] ?? [] as $bar)

                                    @php
                                        $color = 'bg-gray-600';

                                        if ($bar['is_delayed'] ?? false) {
                                            $color = 'bg-red-700';
                                        } elseif ($bar['is_completed'] ?? false) {
                                            $color = 'bg-emerald-700';
                                        } elseif ($bar['is_sailing'] ?? false) {
                                            $color = 'bg-blue-700';
                                        }
                                    @endphp

                                    <div draggable="true"
                                         @dragstart="startDrag({{ $bar['id'] }})"
                                         title="{{ $bar['tooltip'] }}"
                                         class="h-6 rounded text-white text-[10px] px-2 truncate cursor-move shadow-sm
                                                flex items-center
                                                {{ $color }}"
                                         style="grid-column: {{ $bar['start'] }} / {{ $bar['end'] + 1 }};">

                                        <span class="truncate">
                                            {{ $bar['label'] }}
                                        </span>

                                        @if($bar['is_delayed'] ?? false)
                                            <span class="ml-1 text-[8px] bg-black/30 px-1 rounded">
                                                !
                                            </span>
                                        @endif

                                    </div>

                                @endforeach

                                @for ($i = 1; $i <= $calendar['days_count']; $i++)
                                    <div class="border-r border-gray-100"></div>
                                @endfor

                            </div>

                        </td>
                    </tr>

                @endforeach

            </tbody>
        </table>
    </div>

    <div class="px-4 py-3 border-t bg-gray-50 text-xs flex flex-wrap gap-4 items-center">

        <span class="font-semibold text-gray-600">Keterangan:</span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-emerald-700 rounded"></span>
            Selesai
        </span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-blue-700 rounded"></span>
            Berlayar
        </span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-red-700 rounded"></span>
            Terlambat
        </span>

        <span class="flex items-center gap-1">
            <span class="w-3 h-3 bg-gray-600 rounded"></span>
            Terjadwal
        </span>

        <span class="ml-auto text-rose-600 font-medium">
            Hari merah = weekend
        </span>

    </div>

</div>