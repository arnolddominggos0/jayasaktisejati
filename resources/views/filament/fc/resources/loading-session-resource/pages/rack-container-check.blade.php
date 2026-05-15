<x-filament-panels::page>
 <x-filament-panels::form wire:submit="save">
 {{ $this->form }}

 <div class="flex gap-3">
 @foreach($this->getFormActions() as $action)
 {{ $action }}
 @endforeach
 </div>
 </x-filament-panels::form>

 {{-- Summary Panel --}}
 <div class="mt-6 p-4 bg-gray-50 dark:bg-slate-950 rounded-lg">
 <h3 class="text-lg font-semibold mb-4">Ringkasan Status</h3>
 <div class="grid grid-cols-4 gap-4">
 <div class="p-3 bg-white dark:bg-slate-900 rounded border border-gray-200 dark:border-white/5">
 <div class="text-sm text-gray-600 dark:text-slate-400">Pilar A</div>
 <div class="font-medium {{ isset($this->data['pillar_a_condition']) && $this->data['pillar_a_condition'] === 'strong_and_straight' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
 {{ isset($this->data['pillar_a_condition']) ? \App\Enums\RackPillarCondition::from($this->data['pillar_a_condition'])->label() : 'Belum dicek' }}
 </div>
 </div>
 <div class="p-3 bg-white dark:bg-slate-900 rounded border border-gray-200 dark:border-white/5">
 <div class="text-sm text-gray-600 dark:text-slate-400">Pilar B</div>
 <div class="font-medium {{ isset($this->data['pillar_b_condition']) && $this->data['pillar_b_condition'] === 'strong_and_straight' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
 {{ isset($this->data['pillar_b_condition']) ? \App\Enums\RackPillarCondition::from($this->data['pillar_b_condition'])->label() : 'Belum dicek' }}
 </div>
 </div>
 <div class="p-3 bg-white dark:bg-slate-900 rounded border border-gray-200 dark:border-white/5">
 <div class="text-sm text-gray-600 dark:text-slate-400">Pilar C</div>
 <div class="font-medium {{ isset($this->data['pillar_c_condition']) && $this->data['pillar_c_condition'] === 'strong_and_straight' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
 {{ isset($this->data['pillar_c_condition']) ? \App\Enums\RackPillarCondition::from($this->data['pillar_c_condition'])->label() : 'Belum dicek' }}
 </div>
 </div>
 <div class="p-3 bg-white dark:bg-slate-900 rounded border border-gray-200 dark:border-white/5">
 <div class="text-sm text-gray-600 dark:text-slate-400">Pilar D</div>
 <div class="font-medium {{ isset($this->data['pillar_d_condition']) && $this->data['pillar_d_condition'] === 'strong_and_straight' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
 {{ isset($this->data['pillar_d_condition']) ? \App\Enums\RackPillarCondition::from($this->data['pillar_d_condition'])->label() : 'Belum dicek' }}
 </div>
 </div>
 </div>

 @if(isset($this->data['pillar_a_condition']) || isset($this->data['pillar_b_condition']) || isset($this->data['pillar_c_condition']) || isset($this->data['pillar_d_condition']))
 @php
 $hasCritical = false;
 foreach(['pillar_a_condition', 'pillar_b_condition', 'pillar_c_condition', 'pillar_d_condition'] as $pillar) {
 if (isset($this->data[$pillar]) && $this->data[$pillar] === 'damaged') {
 $hasCritical = true;
 break;
 }
 }
 @endphp

 @if($hasCritical)
 <div class="mt-4 p-4 bg-red-100 border border-red-400 text-red-700 dark:text-red-300 rounded">
 <div class="flex items-center gap-2">
 <x-heroicon-o-exclamation-triangle class="w-5 h-5"/>
 <strong>PERINGATAN KRITIS:</strong> Ada pilar yang rusak. Loading TIDAK BOLEH dilanjutkan!
 </div>
 </div>
 @else
 <div class="mt-4 p-4 bg-green-100 border border-green-400 text-green-700 dark:text-green-300 rounded">
 <div class="flex items-center gap-2">
 <x-heroicon-o-check-circle class="w-5 h-5"/>
 Semua pilar dalam kondisi aman.
 </div>
 </div>
 @endif
 @endif
 </div>
</x-filament-panels::page>
