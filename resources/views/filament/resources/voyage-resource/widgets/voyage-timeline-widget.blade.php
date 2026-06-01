<x-filament-widgets::widget>
    @php
        $voyage = $this->getRecord();
    @endphp

    @if ($voyage)
        @include('components.voyage-operational-timeline', ['voyage' => $voyage])
    @endif
</x-filament-widgets::widget>
