<x-filament-panels::page>
 <x-filament-panels::form wire:submit="save">
 {{ $this->form }}

 <div class="fi-form-actions">
 <x-filament::button type="submit" icon="heroicon-m-check">
 Simpan Perubahan
 </x-filament::button>
 </div>
 </x-filament-panels::form>
</x-filament-panels::page>
