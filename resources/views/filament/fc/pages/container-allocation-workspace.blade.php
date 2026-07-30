<x-filament-panels::page>
    {{--
        Container Allocation Workspace — Sprint CA-01.

        Layout mengikuti Domain Freeze §7: LEFT = seluruh Unit yang belum
        masuk container; RIGHT = seluruh Container beserta kapasitas & isi.
        Tidak ada komponen baru — memakai komponen Filament bawaan
        (x-filament::section/badge/button) sesuai Design System yang sudah
        dibekukan.
    --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- LEFT — Unit belum masuk container --}}
        <x-filament::section>
            <x-slot name="heading">
                Unit Belum Masuk Container ({{ $unallocatedUnits->count() }})
            </x-slot>

            @if ($unallocatedUnits->isEmpty())
                <p class="text-sm text-gray-500">
                    Tidak ada unit yang menunggu alokasi saat ini. Unit muncul di sini
                    setelah lolos Inspeksi Handover Depo.
                </p>
            @else
                <div class="flex flex-col divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($unallocatedUnits as $unit)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <div class="flex flex-col">
                                <span class="font-medium text-sm">{{ $unit->reg_no ?? $unit->chassis_no }}</span>
                                <span class="text-xs text-gray-500">
                                    {{ $unit->model_no }}
                                    @if ($unit->shipment?->destinationCity?->name)
                                        &middot; {{ $unit->shipment->destinationCity->name }}
                                    @endif
                                    @if ($unit->shipment?->code)
                                        &middot; {{ $unit->shipment->code }}
                                    @endif
                                </span>
                            </div>
                            <x-filament::button
                                size="sm"
                                wire:click="mountAction('assignToContainer', { unit: {{ $unit->id }} })"
                            >
                                Masukkan ke Container
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>

        {{-- RIGHT — Container beserta kapasitas & isi --}}
        <x-filament::section>
            <x-slot name="heading">
                Container Hari Ini ({{ $containers->count() }})
            </x-slot>

            @if ($containers->isEmpty())
                <p class="text-sm text-gray-500">
                    Belum ada Container Readiness untuk hari ini, sehingga belum ada
                    container yang bisa dipakai alokasi.
                </p>
            @else
                <div class="flex flex-col gap-4">
                    @foreach ($containers as $container)
                        @php
                            // CA-01.5: container transient (belum tersimpan) =
                            // container dari daftar Readiness yang belum disentuh
                            // alokasi. Akses relasi units() hanya bila tersimpan.
                            $isPersisted = $container->exists;
                            $containerNo = $container->container_no;
                            $filled = $container->filledCount();
                            $capacity = $container->capacity;
                            $pct = $capacity ? min(100, (int) round(($filled / max($capacity, 1)) * 100)) : 0;
                        @endphp
                        <div class="rounded-lg border border-gray-200 dark:border-white/10 p-3">
                            <div class="flex items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="font-semibold text-sm">{{ $containerNo }}</span>
                                    @if ($container->type)
                                        <x-filament::badge color="gray">{{ $container->type->label() }}</x-filament::badge>
                                    @else
                                        <x-filament::badge color="warning">Belum dilengkapi</x-filament::badge>
                                    @endif
                                    @if ($container->is_ready_for_stuffing)
                                        <x-filament::badge color="success">Siap Stuffing</x-filament::badge>
                                    @endif
                                </div>

                                <div class="flex items-center gap-1.5">
                                    @if ($container->type === null || $container->capacity === null)
                                        <x-filament::button
                                            size="xs"
                                            color="gray"
                                            wire:click="mountAction('configureContainer', { containerNo: @js($containerNo) })"
                                        >
                                            Lengkapi
                                        </x-filament::button>
                                    @elseif ($container->is_ready_for_stuffing)
                                        <x-filament::button
                                            size="xs"
                                            color="gray"
                                            wire:click="mountAction('unmarkContainerReady', { containerNo: @js($containerNo) })"
                                        >
                                            Batalkan Tanda Siap
                                        </x-filament::button>
                                    @else
                                        <x-filament::button
                                            size="xs"
                                            color="success"
                                            wire:click="mountAction('markContainerReady', { containerNo: @js($containerNo) })"
                                        >
                                            Tandai Siap Stuffing
                                        </x-filament::button>
                                    @endif
                                </div>
                            </div>

                            @if ($capacity)
                                <div class="mt-2 flex items-center gap-2">
                                    <div class="h-1.5 flex-1 rounded-full bg-gray-100 dark:bg-white/10 overflow-hidden">
                                        <div class="h-full bg-primary-500" style="width: {{ $pct }}%"></div>
                                    </div>
                                    <span class="text-xs text-gray-500 whitespace-nowrap">{{ $filled }}/{{ $capacity }}</span>
                                </div>
                            @endif

                            @if ($isPersisted && $container->units->isNotEmpty())
                                <div class="mt-2.5 flex flex-col divide-y divide-gray-100 dark:divide-white/5">
                                    @foreach ($container->units as $unit)
                                        <div class="flex items-center justify-between gap-2 py-1.5">
                                            <span class="text-sm">
                                                {{ $unit->reg_no ?? $unit->chassis_no }}
                                                <span class="text-xs text-gray-500">{{ $unit->model_no }}</span>
                                            </span>
                                            @unless ($container->is_ready_for_stuffing)
                                                <div class="flex items-center gap-1">
                                                    <x-filament::icon-button
                                                        icon="heroicon-m-arrow-right-circle"
                                                        label="Pindahkan"
                                                        wire:click="mountAction('moveToContainer', { unit: {{ $unit->id }} })"
                                                    />
                                                    <x-filament::icon-button
                                                        icon="heroicon-m-x-circle"
                                                        label="Keluarkan"
                                                        color="danger"
                                                        wire:click="mountAction('removeFromContainer', { unit: {{ $unit->id }} })"
                                                    />
                                                </div>
                                            @endunless
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            @endif
        </x-filament::section>
    </div>

    {{-- Sprint OPS-06 (Scope 4): entry-point ke StuffingWorkspace yang sudah
         ada (ST-01) — muncul begitu SELURUH container milik satu shipment
         sudah ditandai Siap Stuffing. Tidak ada planning apa pun terjadi di
         sini; ini murni tautan berbasis status yang sudah tersimpan. --}}
    @if ($shipmentsReadyForStuffing->isNotEmpty())
        <div class="mt-6">
            <x-filament::section>
                <x-slot name="heading">
                    Siap untuk Stuffing ({{ $shipmentsReadyForStuffing->count() }})
                </x-slot>

                <div class="flex flex-col divide-y divide-gray-100 dark:divide-white/5">
                    @foreach ($shipmentsReadyForStuffing as $shipment)
                        <div class="flex items-center justify-between gap-3 py-2.5">
                            <span class="font-medium text-sm">{{ $shipment->code }}</span>
                            <x-filament::button
                                size="sm"
                                color="success"
                                tag="a"
                                href="{{ \App\Filament\FC\Pages\StuffingWorkspace::getUrl(['shipment' => $shipment->id]) }}"
                            >
                                Mulai Stuffing
                            </x-filament::button>
                        </div>
                    @endforeach
                </div>
            </x-filament::section>
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>
