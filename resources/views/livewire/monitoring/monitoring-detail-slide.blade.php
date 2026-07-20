{{--
    Operational Detail Panel

    Livewire root is always in the DOM. Visibility is driven by CSS class
    `is-open` toggled by Alpine — no x-show, so the transform animation
    runs on the real element without Alpine needing to insert/remove it.

    Close triggers (backdrop, close button) dispatch the browser CustomEvent
    `panel-close-requested` which bubbles to the outer monWorkspace Alpine
    context, which calls $wire.closeDetail() on WorkspaceShell.
    WorkspaceShell relays `close-detail` to this component, which then
    dispatches `detail-closed` for Alpine to restore focus.

    Skeleton: wire:loading.delay shows while Livewire is serving load().
--}}
<div
    x-data="{
        isOpen: false,
        init() {
            const self = this;
            window.addEventListener('open-unit-detail', function() {
                self.isOpen = true;
                self.$nextTick(function() {
                    var btn = self.$el.querySelector('[data-close-btn]');
                    if (btn) btn.focus();
                });
            });
            window.addEventListener('detail-closed', function() {
                self.isOpen = false;
            });
        }
    }"
>

    {{-- Backdrop --}}
    <div
        class="jss-detail-overlay"
        :class="{ 'is-open': isOpen }"
        aria-hidden="true"
        @click="$dispatch('panel-close-requested')"
    ></div>

    {{-- Panel --}}
    <div
        class="jss-detail-panel"
        :class="{ 'is-open': isOpen }"
        id="detail-panel"
        role="dialog"
        aria-modal="true"
        aria-labelledby="detail-panel-title"
        :aria-hidden="!isOpen"
        tabindex="-1"
    >

        {{-- Panel Header --}}
        <div class="jss-detail-header">
            <div class="min-w-0 flex-1">
                <p class="jss-detail-title" id="detail-panel-title">
                    {{ $unitDetail ? $unitDetail->unit_reg_no : 'Detail Shipment' }}
                </p>
                @if ($unitDetail)
                    <p class="jss-detail-subtitle">
                        {{ $unitDetail->shipment_code }}
                        @if ($unitDetail->route_label)
                            &middot; {{ $unitDetail->route_label }}
                        @endif
                    </p>
                @endif
            </div>
            <button
                type="button"
                class="jss-detail-close"
                aria-label="Tutup detail panel (Esc)"
                data-close-btn
                @click="$dispatch('panel-close-requested')"
            >
                <x-heroicon-o-x-mark class="w-5 h-5" />
            </button>
        </div>

        {{-- Panel Body --}}
        <div class="jss-detail-body">

            {{-- Skeleton (shown while Livewire request is in-flight) --}}
            <div wire:loading.delay>
                <div class="jss-detail-section mb-2.5">
                    <div class="jss-detail-section-head">
                        <span class="jss-detail-skel h-2.5 w-24 rounded"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach (range(1, 6) as $_)
                            <span class="jss-detail-skel h-3 w-full"></span>
                            <span class="jss-detail-skel h-3 w-3/4"></span>
                        @endforeach
                    </div>
                </div>
                <div class="jss-detail-section mb-2.5">
                    <div class="jss-detail-section-head">
                        <span class="jss-detail-skel h-2.5 w-16 rounded"></span>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="jss-detail-skel h-2 flex-1 rounded-full"></span>
                        <span class="jss-detail-skel h-3 w-8"></span>
                    </div>
                    <span class="jss-detail-skel h-2.5 w-40 mt-2 block"></span>
                </div>
                <div class="jss-detail-section mb-2.5">
                    <div class="jss-detail-section-head">
                        <span class="jss-detail-skel h-2.5 w-20 rounded"></span>
                    </div>
                    <div class="flex flex-col gap-2">
                        @foreach (range(1, 4) as $_)
                            <span class="jss-detail-skel h-3 w-full"></span>
                        @endforeach
                    </div>
                </div>
                <div class="jss-detail-section">
                    <div class="jss-detail-section-head">
                        <span class="jss-detail-skel h-2.5 w-28 rounded"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-2.5">
                        @foreach (range(1, 4) as $_)
                            <span class="jss-detail-skel h-3 w-full"></span>
                            <span class="jss-detail-skel h-3 w-2/3"></span>
                        @endforeach
                    </div>
                </div>
            </div>

            {{-- Content (shown when Livewire response is ready) --}}
            <div wire:loading.remove.delay>

                @if ($shipmentNotFound)
                    {{-- Not-found --}}
                    <div class="jss-detail-not-found">
                        <x-heroicon-o-magnifying-glass class="w-8 h-8 opacity-30" />
                        <p>Shipment tidak ditemukan</p>
                    </div>

                @elseif ($unitDetail)

                    {{-- 1. Identitas Unit --}}
                    <div class="jss-detail-section">
                        <div class="jss-detail-section-head">
                            <span class="jss-detail-section-title">Identitas Unit</span>
                        </div>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">No. Polisi</dt>
                            <dd class="mon-table font-semibold" style="color:var(--mon-navy-600)">{{ $unitDetail->unit_reg_no }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Model</dt>
                            <dd class="mon-table">{{ $unitDetail->unit_model_no ?? '—' }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Warna</dt>
                            <dd class="mon-table">{{ $unitDetail->unit_color }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Chassis</dt>
                            <dd class="mon-table">{{ $unitDetail->unit_chassis_no ?? '—' }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Engine</dt>
                            <dd class="mon-table">{{ $unitDetail->unit_engine_no ?? '—' }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">SJKB</dt>
                            <dd class="mon-table">
                                @if ($unitDetail->unit_sjkb_no)
                                    {{ $unitDetail->unit_sjkb_no }}
                                @else
                                    <span class="jss-detail-empty-val">Belum diterbitkan</span>
                                @endif
                            </dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Container</dt>
                            <dd class="mon-table">
                                @if ($unitDetail->container_display)
                                    {{ $unitDetail->container_display }}
                                @else
                                    <span class="jss-detail-empty-val">Belum di-assign</span>
                                @endif
                            </dd>
                        </dl>
                    </div>

                    {{-- 2. Status Operasional --}}
                    <div class="jss-detail-section">
                        <div class="jss-detail-section-head">
                            <span class="jss-detail-section-title">Status Operasional</span>
                            <span class="mon-caption">{{ $unitDetail->age->label }}</span>
                        </div>

                        <div class="flex items-center gap-3 mb-3">
                            <div class="mon-progress flex-1" role="progressbar"
                                aria-valuenow="{{ $unitDetail->progress_pct }}"
                                aria-valuemin="0" aria-valuemax="100">
                                <div class="mon-progress-fill" style="width:{{ $unitDetail->progress_pct }}%"></div>
                            </div>
                            <span class="mon-pct">{{ $unitDetail->progress_pct }}%</span>
                        </div>

                        <div class="flex items-center gap-2 mb-2">
                            <span class="mon-badge mon-badge-accent">{{ $unitDetail->stage->stage_label }}</span>
                            @if ($unitDetail->stage->is_cancelled)
                                <span class="mon-badge mon-badge-neutral">Dibatalkan</span>
                            @elseif ($unitDetail->stage->is_held)
                                <span class="mon-badge mon-badge-warning">Ditahan</span>
                            @elseif ($unitDetail->stage->is_delivered)
                                <span class="mon-badge mon-badge-success">Selesai</span>
                            @else
                                <span class="mon-badge mon-badge-success">Aktif</span>
                            @endif
                        </div>

                        @if ($unitDetail->admin->last_tracked_at)
                            <p class="mon-foot mt-1" style="color:var(--mon-neutral-400)">
                                Pergerakan terakhir:
                                {{ $unitDetail->admin->last_tracked_at->format('d M Y, H:i') }}
                            </p>
                        @else
                            <p class="mon-foot mt-1" style="color:var(--mon-neutral-400)">Belum ada pergerakan tercatat</p>
                        @endif
                    </div>

                    {{-- 3. Konteks Shipment --}}
                    <div class="jss-detail-section">
                        <div class="jss-detail-section-head">
                            <span class="jss-detail-section-title">Konteks Shipment</span>
                        </div>

                        <p class="jss-detail-sub-label">Shipment</p>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 mb-3">
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Kode</dt>
                            <dd class="mon-table font-semibold" style="color:var(--mon-navy-600)">{{ $unitDetail->shipment_code }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">SPPB</dt>
                            <dd class="mon-table">{{ $unitDetail->doc_number }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Customer</dt>
                            <dd class="mon-table">{{ $unitDetail->customer_name }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Branch</dt>
                            <dd class="mon-table">{{ $unitDetail->branch_name ?? '—' }}</dd>
                        </dl>

                        <p class="jss-detail-sub-label">Rute</p>
                        <div class="jss-detail-route mb-3">
                            <span class="jss-detail-route-point">{{ $unitDetail->admin->pol_name ?? '—' }}</span>
                            <span class="jss-detail-route-arrow">↓</span>
                            <span class="jss-detail-route-point">{{ $unitDetail->admin->pod_name ?? '—' }}</span>
                        </div>

                        <p class="jss-detail-sub-label">Voyage</p>
                        @if ($unitDetail->admin->voyage_no || $unitDetail->admin->vessel_name)
                            <dl class="grid grid-cols-2 gap-x-4 gap-y-2 mb-3">
                                <dt class="mon-foot" style="color:var(--mon-neutral-400)">Voyage</dt>
                                <dd class="mon-table">{{ $unitDetail->admin->voyage_no ?? '—' }}</dd>

                                <dt class="mon-foot" style="color:var(--mon-neutral-400)">Vessel</dt>
                                <dd class="mon-table">{{ $unitDetail->admin->vessel_name ?? '—' }}</dd>
                            </dl>
                        @else
                            <p class="jss-detail-empty-val mb-3">Belum ditentukan</p>
                        @endif

                        <p class="jss-detail-sub-label">Jadwal</p>
                        <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Diminta</dt>
                            <dd class="mon-table">{{ $unitDetail->admin->requested_at?->format('d M Y') ?? '—' }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">ETD</dt>
                            <dd class="mon-table">{{ $unitDetail->admin->etd?->format('d M Y') ?? '—' }}</dd>

                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">ETA</dt>
                            <dd class="mon-table">{{ $unitDetail->admin->eta?->format('d M Y') ?? '—' }}</dd>
                        </dl>
                    </div>

                    {{-- 4. Unit dalam Shipment --}}
                    <div class="jss-detail-section">
                        <div class="jss-detail-section-head">
                            <span class="jss-detail-section-title">Unit dalam Shipment</span>
                            <span class="mon-caption">{{ count($unitDetail->sibling_units) }} unit</span>
                        </div>

                        @if (count($unitDetail->sibling_units) <= 1)
                            <p class="mon-foot" style="color:var(--mon-neutral-400)">
                                Shipment ini hanya memiliki 1 unit.
                            </p>
                        @else
                            <div class="jss-sibling-list">
                                @foreach ($unitDetail->sibling_units as $sibling)
                                    <div class="jss-sibling-item {{ $sibling->unit_id === $unitDetail->unit_id ? 'is-self' : '' }}">
                                        <div class="flex items-center justify-between gap-2">
                                            <span class="mon-unit-code">{{ $sibling->reg_no ?? '—' }}</span>
                                            @if ($sibling->unit_id === $unitDetail->unit_id)
                                                <span class="mon-badge mon-badge-accent" style="font-size:0.5625rem">Unit ini</span>
                                            @endif
                                        </div>
                                        <span class="mon-foot" style="color:var(--mon-neutral-400)">{{ $sibling->stage_label }}</span>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>

                    {{-- Timeline --}}
                    <div class="jss-detail-section">
                        <div class="jss-detail-section-head">
                            <span class="jss-detail-section-title">Timeline</span>
                            <span class="mon-caption">{{ $unitDetail->timeline->completed_count }}/{{ $unitDetail->timeline->total_count }}</span>
                        </div>
                        @include('livewire.monitoring.unit-timeline', ['timeline' => $unitDetail->timeline])
                    </div>

                    {{-- Inspeksi (placeholder) --}}
                    <div class="jss-detail-section">
                        <div class="jss-detail-section-head">
                            <span class="jss-detail-section-title">Inspeksi</span>
                        </div>
                        <p class="jss-detail-placeholder" style="padding:0.625rem 0">Segera hadir</p>
                    </div>

                    {{-- Deep Links --}}
                    @if (!empty($unitDetail->deep_links))
                        <div class="flex flex-wrap gap-2 pt-1">
                            @foreach ($unitDetail->deep_links as $link)
                                <a href="{{ $link->url }}" class="mon-deeplink">
                                    {{ $link->label }}
                                    <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                                </a>
                            @endforeach
                        </div>
                    @endif

                @else
                    {{-- Initial state before any unit is selected (panel is closed, not visible) --}}
                    <p class="jss-detail-placeholder">Pilih unit dari tabel</p>
                @endif

            </div>{{-- /wire:loading.remove --}}

        </div>{{-- /jss-detail-body --}}

    </div>{{-- /jss-detail-panel --}}

</div>{{-- /x-data --}}
