<x-filament::page>
    {{ $this->form }}
    @if ($this->sessionId)
        <x-filament::section>
            <x-slot name="heading">Absensi & Checksheet</x-slot>
            {{ $this->table }}
        </x-filament::section>
    @endif
</x-filament::page>
