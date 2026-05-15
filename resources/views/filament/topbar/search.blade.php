@if (request()->routeIs('filament.*.pages.dashboard'))
 <div class="jss-search-wrap">
 <div class="jss-search">
 <x-filament::input.wrapper size="md" prefix-icon="heroicon-o-magnifying-glass" class="w-full">
 <x-filament::input placeholder="Pencarian" />
 </x-filament::input.wrapper>
 </div>
 </div>
@endif
