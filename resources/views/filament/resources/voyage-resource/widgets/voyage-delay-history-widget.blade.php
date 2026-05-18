<x-filament-widgets::widget>
    @php
        $voyage = $this->getRecord();
    @endphp

    @if ($voyage)
        @include('filament.resources.voyage-resource.widgets.delay-history', ['logs' => $voyage->delayLogs])
    @endif
</x-filament-widgets::widget>
