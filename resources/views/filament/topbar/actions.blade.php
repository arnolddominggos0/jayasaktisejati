<div class="hidden md:flex items-center gap-3 pl-2">
  {{-- Bell UI --}}
  <button type="button"
    class="relative inline-flex h-9 w-9 items-center justify-center rounded-xl border border-slate-200 hover:bg-slate-100">
    <x-filament::icon icon="heroicon-o-bell" class="h-5 w-5 text-slate-700" />
    <span class="absolute -top-0.5 -right-0.5 h-2 w-2 rounded-full bg-rose-500"></span>
  </button>

  {{-- Export UI --}}
  <x-filament::button size="sm" color="primary" icon="heroicon-o-arrow-down-tray" class="rounded-xl" tag="a" href="#">
    Export
  </x-filament::button>
</div>
