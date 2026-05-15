<x-filament-panels::page>
 <x-filament-panels::form wire:submit="save">
 {{ $this->form }}

 <div class="flex gap-3">
 @foreach($this->getFormActions() as $action)
 {{ $action }}
 @endforeach
 </div>
 </x-filament-panels::form>
</x-filament-panels::page>
