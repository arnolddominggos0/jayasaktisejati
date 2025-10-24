<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between mb-2">
            <div class="text-sm font-semibold">
                Jadwal Kapal (TAM) —
                {{-- {{ \Illuminate\Support\Carbon::parse($days[0] ?? now())->translatedFormat('F Y') }} --}}
            </div>
            <div class="text-xs text-gray-500">
                Hanya menampilkan jadwal dari status <b>Final</b>.
            </div>
        </div>

        <style>
            .grid-cal {
                display: grid;
                grid-template-columns: 160px repeat({{ count($days) }}, 1fr);
                gap: 2px;
            }

            .cell {
                padding: 6px 8px;
                background: #fff;
                min-height: 34px;
                border: 1px solid #e5e7eb;
            }

            .head {
                background: #f3f4f6;
                font-weight: 600;
                text-align: center;
                position: sticky;
                top: 0;
                z-index: 1;
            }

            .rowname {
                background: #fafafa;
                font-weight: 600;
                position: sticky;
                left: 0;
                z-index: 2;
            }

            .chip {
                display: inline-block;
                padding: 2px 6px;
                border-radius: 6px;
                background: #eef2ff;
                font-weight: 700;
                margin: 1px;
                font-size: .75rem;
                letter-spacing: .5px;
            }

            .num {
                font-weight: 700;
                text-align: center;
                background: #fff7ed;
            }

            .wrap {
                overflow-x: auto;   
            }
        </style>

        <div class="wrap">
            <div class="grid-cal">
                <div class="head cell"></div>
                @foreach ($days as $d)
                    <div class="head cell">{{ \Illuminate\Support\Carbon::parse($d)->format('d') }}</div>
                @endforeach

                @foreach ($rows as $rowName => $cols)
                    <div class="rowname cell">{{ $rowName }}</div>
                    @foreach ($cols as $d => $html)
                        @php $isNum = is_numeric($html); @endphp
                        <div class="cell {{ $isNum ? 'num' : '' }}">{!! $html !== '' ? $html : '&nbsp;' !!}</div>
                    @endforeach
                @endforeach
            </div>
        </div>
    </x-filament::card>
</x-filament::widget>
