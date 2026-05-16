<x-filament-widgets::widget>
    @php
        $voyage = $this->getRecord();
    @endphp

    @if ($voyage)
        <div class="space-y-3">
            <div class="text-sm font-semibold text-gray-700">
                Delay History & Audit Trail
            </div>
            @include('filament.resources.voyage-resource.widgets.delay-history', ['logs' => $voyage->delayLogs])
        </div>
    @endif
</x-filament-widgets::widget>
