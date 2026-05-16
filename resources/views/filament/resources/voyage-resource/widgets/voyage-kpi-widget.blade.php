<x-filament-widgets::widget>
    @php
        $voyage = $this->getRecord();
    @endphp

    @if ($voyage)
        @include('components.voyage-kpi-panel', ['voyage' => $voyage])
    @endif
</x-filament-widgets::widget>
