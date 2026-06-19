<x-filament-panels::page>

    {{-- ══════════════════════════════════════════════════════════════════════
         SETUP HARI INI — shortcut panel untuk dua task wajib pagi FC:
         Card A: Briefing Harian  ·  Card B: Container Readiness
         Tidak ada logic baru — hanya menampilkan status & link ke halaman yang ada.
    ══════════════════════════════════════════════════════════════════════ --}}
    @php $setup = $this->getDailySetup(); @endphp

    <div class="mb-6">
        <div class="mb-2 flex items-center gap-2 px-1">
            <x-heroicon-o-sun class="h-4 w-4 text-gray-400 dark:text-gray-500" />
            <span class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                Setup Hari Ini
            </span>
        </div>

        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">

            {{-- ─ Card A: Briefing Harian ──────────────────────────────────── --}}
            @php
                $briefingExists = $setup['briefing']['exists'];
                $briefingIcon   = $briefingExists ? 'bg-emerald-50 dark:bg-emerald-900/20' : 'bg-amber-50 dark:bg-amber-900/20';
                $briefingCta    = $briefingExists ? $setup['briefing']['view_url'] : $setup['briefing']['create_url'];
                $briefingBtnCls = $briefingExists
                    ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/40'
                    : 'bg-amber-600 text-white shadow-sm hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600';
            @endphp
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5
                        shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $briefingIcon }}">
                        @if ($briefingExists)
                            <x-heroicon-o-clipboard-document-check class="h-6 w-6 text-emerald-600 dark:text-emerald-400" />
                        @else
                            <x-heroicon-o-clock class="h-6 w-6 text-amber-600 dark:text-amber-400" />
                        @endif
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Briefing Hari Ini
                        </p>
                        @if ($briefingExists)
                            <p class="mt-0.5 truncate text-sm font-semibold text-gray-800 dark:text-gray-200">
                                Sesi Tersedia
                            </p>
                            <p class="truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $setup['briefing']['status_label'] }}
                            </p>
                        @else
                            <p class="mt-0.5 text-sm font-semibold text-amber-700 dark:text-amber-400">
                                Belum Ada Sesi
                            </p>
                        @endif
                    </div>
                </div>
                <a href="{{ $briefingCta }}"
                   class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-4 py-2
                          text-sm font-semibold transition-colors {{ $briefingBtnCls }}">
                    @if ($briefingExists)
                        <x-heroicon-o-eye class="h-4 w-4" />
                        Lihat Briefing
                    @else
                        <x-heroicon-o-plus-circle class="h-4 w-4" />
                        Buat Briefing
                    @endif
                </a>
            </div>

            {{-- ─ Card B: Container Readiness ──────────────────────────────── --}}
            @php
                $containerExists = $setup['container']['exists'];
                $containerReady  = $setup['container']['is_ready'];
                $containerCta    = $containerExists ? $setup['container']['edit_url'] : $setup['container']['create_url'];

                if (! $containerExists) {
                    $containerIconCls = 'bg-amber-50 dark:bg-amber-900/20';
                    $containerIconCol = 'text-amber-600 dark:text-amber-400';
                    $containerBtnCls  = 'bg-amber-600 text-white shadow-sm hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600';
                } elseif ($containerReady) {
                    $containerIconCls = 'bg-emerald-50 dark:bg-emerald-900/20';
                    $containerIconCol = 'text-emerald-600 dark:text-emerald-400';
                    $containerBtnCls  = 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 hover:bg-emerald-100 dark:bg-emerald-900/20 dark:text-emerald-300 dark:ring-emerald-800 dark:hover:bg-emerald-900/40';
                } else {
                    $containerIconCls = 'bg-rose-50 dark:bg-rose-900/20';
                    $containerIconCol = 'text-rose-600 dark:text-rose-400';
                    $containerBtnCls  = 'bg-rose-50 text-rose-700 ring-1 ring-rose-200 hover:bg-rose-100 dark:bg-rose-900/20 dark:text-rose-300 dark:ring-rose-800 dark:hover:bg-rose-900/40';
                }
            @endphp
            <div class="flex items-center justify-between gap-4 rounded-xl bg-white p-5
                        shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex min-w-0 items-center gap-4">
                    <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl {{ $containerIconCls }}">
                        <x-heroicon-o-archive-box class="h-6 w-6 {{ $containerIconCol }}" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Container Readiness
                        </p>
                        @if ($containerExists)
                            <p class="mt-0.5 truncate text-sm font-semibold
                                      {{ $containerReady ? 'text-emerald-700 dark:text-emerald-400' : 'text-rose-700 dark:text-rose-400' }}">
                                Sudah Diinput
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                {{ $containerReady ? 'READY' : 'NOT READY' }}
                            </p>
                        @else
                            <p class="mt-0.5 text-sm font-semibold text-amber-700 dark:text-amber-400">
                                Belum Diinput
                            </p>
                        @endif
                    </div>
                </div>
                <a href="{{ $containerCta }}"
                   class="shrink-0 inline-flex items-center gap-1.5 rounded-lg px-4 py-2
                          text-sm font-semibold transition-colors {{ $containerBtnCls }}">
                    @if ($containerExists)
                        <x-heroicon-o-pencil-square class="h-4 w-4" />
                        Lihat Container
                    @else
                        <x-heroicon-o-plus-circle class="h-4 w-4" />
                        Input Container
                    @endif
                </a>
            </div>

        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         SHIPMENT TASK TABLE — existing, tidak ada perubahan
    ══════════════════════════════════════════════════════════════════════ --}}
    {{ $this->table }}

</x-filament-panels::page>
