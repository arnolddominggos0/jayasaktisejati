@php
    $tracks = $getState();

    $statusBg = function(string $val): string {
        return match ($val) {
            'delivered'            => 'border-l-4 border-green-400 bg-green-50 dark:bg-green-900/10',
            'cancelled'            => 'border-l-4 border-red-400 bg-red-50 dark:bg-red-900/10',
            'vessel_arrival'       => 'border-l-4 border-blue-300 dark:border-blue-700',
            'vessel_depart','onship' => 'border-l-4 border-indigo-400',
            default                => 'border-l-4 border-gray-200 dark:border-gray-700',
        };
    };

    $statusColor = function(string $val): string {
        return match ($val) {
            'delivered'            => 'text-green-700 dark:text-green-400',
            'cancelled'            => 'text-red-700 dark:text-red-400',
            'vessel_arrival','unloading','delivery_to_customer' => 'text-blue-700 dark:text-blue-400',
            'vessel_depart','onship','unit_loading','stuffing','stacking' => 'text-indigo-700 dark:text-indigo-400',
            'pickup','handover','delivery_to_port' => 'text-amber-700 dark:text-amber-400',
            'hold'                 => 'text-orange-700 dark:text-orange-400',
            default                => 'text-gray-700 dark:text-gray-300',
        };
    };
@endphp

@if ($tracks->isEmpty())
    <p class="text-sm text-gray-400 dark:text-gray-500 italic py-2">
        Belum ada data tracking untuk pengiriman ini.
    </p>
@else
    <div class="space-y-px">
        @foreach ($tracks as $track)
            @php
                $statusVal   = $track->status instanceof \App\Enums\TrackStatus
                    ? $track->status->value
                    : (string) ($track->status ?? '');
                $statusLabel = $track->status instanceof \App\Enums\TrackStatus
                    ? $track->status->label()
                    : ucfirst(str_replace('_', ' ', $statusVal));
                $bg    = $statusBg($statusVal);
                $color = $statusColor($statusVal);
            @endphp

            <div class="pl-4 pr-3 py-3 rounded-r {{ $bg }}">

                {{-- Date ------------------------------------------------}}
                @if ($track->tracked_at)
                    <div class="text-xs text-gray-400 dark:text-gray-500 mb-0.5 tabular-nums">
                        {{ \Carbon\Carbon::parse($track->tracked_at)->format('d M Y') }}
                    </div>
                @endif

                {{-- Status ----------------------------------------------}}
                <div class="text-sm font-semibold {{ $color }} leading-snug">
                    {{ $statusLabel }}
                </div>

                {{-- Location --------------------------------------------}}
                @if ($track->location)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                        {{ $track->location }}
                    </div>
                @endif

                {{-- Notes -----------------------------------------------}}
                @if ($track->note)
                    <div class="text-xs text-gray-400 dark:text-gray-500 mt-0.5 italic">
                        {{ $track->note }}
                    </div>
                @endif

            </div>
        @endforeach
    </div>

    <p class="mt-2 text-xs text-gray-300 dark:text-gray-600 pl-1">
        {{ $tracks->count() }} event &middot; urutan kronologis &middot; read-only
    </p>
@endif
