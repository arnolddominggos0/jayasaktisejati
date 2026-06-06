<x-filament-panels::page>
    <div class="space-y-4 p-4">

        <select wire:model.live="period"
            class="rounded border-gray-300 text-sm py-1.5 px-2">
            <option value="2026-05">Mei 2026</option>
            <option value="2026-06">Juni 2026</option>
            <option value="2026-07">Juli 2026</option>
        </select>

        <div class="text-sm font-mono text-gray-700">
            period = {{ $period }}
        </div>

    </div>
</x-filament-panels::page>
