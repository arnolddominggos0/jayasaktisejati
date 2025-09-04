<x-filament::section>
    <x-slot name="heading">
        Aktivitas Terbaru
    </x-slot>

    <div class="space-y-2 max-h-80 overflow-y-auto pr-1">
        @foreach ($this->getRecords() as $item)
            @php
                $state = (string) $item->status; // enum -> string aman
                $badgeColor = match($state){
                    'draft' => 'gray',
                    'pending' => 'warning',
                    'pickup','transit' => 'info',
                    'delivered' => 'success',
                    'hold' => 'warning',
                    'cancelled' => 'danger',
                    default => 'gray',
                };
            @endphp

            <div class="flex items-center justify-between gap-2 text-sm">
                <div class="truncate">
                    <span class="font-medium text-gray-900 dark:text-gray-100">{{ $item->code }}</span>
                    <span class="text-gray-500 dark:text-gray-400 ml-1">• {{ $item->updated_at->diffForHumans() }}</span>
                </div>
                <x-filament::badge :color="$badgeColor">
                    {{ Str::of($state)->replace('_',' ')->title() }}
                </x-filament::badge>
            </div>
        @endforeach
    </div>

    <x-slot name="footer">
        <a class="text-primary-600 hover:underline text-sm" href="{{ \App\Filament\Resources\ShipmentResource::getUrl() }}">
            Lihat semua
        </a>
    </x-slot>
</x-filament::section>
