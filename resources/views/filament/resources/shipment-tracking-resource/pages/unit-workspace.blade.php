{{--
    Detail Unit Workspace (v1.0) — Operational Workspace, BUKAN halaman CRUD.

    Bound ke App\ViewModels\Monitoring\UnitDetailData ($detail) yang dibangun
    DetailUnitProvider. NO query / NO business logic di sini — hanya menyusun
    data yang sudah ada ke dalam urutan kerja coordinator:

        Hero → Timeline → Informasi Operasional → Pemeriksaan → Riwayat → Dokumen

    Seluruh kelas visual (.jss-detail-*, .mon-*, .jss-timeline) berasal dari
    Design System yang sudah dibekukan. Tidak ada CSS baru.
--}}
@php
    use App\Enums\ShipmentMode;
    use App\Filament\Resources\ShipmentTrackingResource;
    use Illuminate\Support\Str;

    /** @var App\ViewModels\Monitoring\UnitDetailData $detail */
    $admin = $detail->admin;

    // Status Operasional (hero): exception terparah jika ada, jika tidak → status tahap.
    $worstException = !empty($detail->exceptions) ? $detail->exceptions[0] : null;
    if ($worstException) {
        $statusLabel = $worstException->label;
        $statusClass = $worstException->severity === 'critical' ? 'mon-badge-danger' : 'mon-badge-warning';
    } elseif ($detail->stage->is_cancelled) {
        $statusLabel = 'Dibatalkan';  $statusClass = 'mon-badge-danger';
    } elseif ($detail->stage->is_held) {
        $statusLabel = 'Ditahan';     $statusClass = 'mon-badge-warning';
    } elseif ($detail->stage->is_delivered) {
        $statusLabel = 'Selesai';     $statusClass = 'mon-badge-success';
    } else {
        $statusLabel = 'Berjalan Normal'; $statusClass = 'mon-badge-success';
    }

    $modeLabel = $detail->mode instanceof ShipmentMode ? $detail->mode->label() : (string) $detail->mode;
    $priorityLabel = match (strtolower((string) $admin->priority)) {
        'urgent' => 'Mendesak', 'normal' => 'Normal', default => $admin->priority ?: '—',
    };
    $voyageText = $admin->voyage_no
        ? trim(display_voyage($admin->voyage_no) . ($admin->vessel_name ? ' — ' . $admin->vessel_name : ''))
        : null;
    $routeTitle = Str::title($detail->route_label);

    // Riwayat Aktivitas: tahap timeline yang sudah selesai, terbaru di atas.
    $activity = collect($detail->timeline->stages)
        ->filter(fn ($s) => ($s->state ?? null) === 'completed' && ($s->tracked_at ?? null))
        ->sortByDesc(fn ($s) => $s->tracked_at)
        ->values();

    $manageUrl = ShipmentTrackingResource::getUrl('manage', ['record' => $detail->shipment_id]);
@endphp

<x-filament-panels::page>
    <div class="flex flex-col gap-4">

        {{-- ══════════════════════════════════════════════════════════════
             SECTION 1 — HERO SUMMARY
             Identitas unit + kondisi + quick action. <3–5 detik.
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-detail-section" aria-label="Ringkasan unit">
            <div class="flex flex-wrap items-start justify-between gap-4">
                {{-- Identitas + status --}}
                <div class="flex-1 min-w-[16rem]">
                    <div class="flex items-center gap-3 flex-wrap">
                        <span class="text-2xl font-bold" style="color:var(--mon-navy-600)">{{ $detail->unit_reg_no }}</span>
                        <span class="mon-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </div>
                    <p class="mon-table mt-0.5">{{ $detail->unit_model_no ?? '—' }}</p>

                    {{-- Ringkasan kunci: Tahap · Dwelling · Voyage · ETA · Rute --}}
                    <dl class="grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 mt-4">
                        <div>
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Tahap</dt>
                            <dd class="mon-table font-semibold">{{ $detail->stage->stage_label }}</dd>
                        </div>
                        <div>
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Dwelling</dt>
                            <dd class="mon-table font-semibold">{{ $detail->age->label }}</dd>
                        </div>
                        <div>
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Voyage</dt>
                            <dd class="mon-table font-semibold">{{ $voyageText ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">ETA</dt>
                            <dd class="mon-table font-semibold">{{ $admin->eta?->translatedFormat('d M Y') ?? '—' }}</dd>
                        </div>
                        <div class="col-span-2 md:col-span-1">
                            <dt class="mon-foot" style="color:var(--mon-neutral-400)">Rute</dt>
                            <dd class="mon-table font-semibold">{{ $routeTitle }}</dd>
                        </div>
                    </dl>
                </div>

                {{-- Quick Action (v1.1 audit): HANYA aksi operasional yang
                     mewakili pekerjaan coordinator. Navigasi "Lihat Dokumen"/
                     "Lihat Riwayat" dihapus — section-nya sudah berada di
                     halaman yang sama (bukan aksi, cukup di-scroll). Aksi lain
                     (Input Pemeriksaan, Upload Dokumen) menyusul saat flow-nya
                     dibangun — di luar scope sprint freeze ini. --}}
                <div class="flex flex-col gap-2 min-w-[11rem]">
                    <a href="{{ $manageUrl }}" class="mon-deeplink">
                        Update Tahap
                        <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                    </a>
                </div>
            </div>
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             SECTION 2 — TIMELINE OPERASIONAL  (pusat halaman)
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-detail-section" aria-label="Timeline operasional">
            <div class="jss-detail-section-head">
                <span class="jss-detail-section-title">Timeline Operasional</span>
                <span class="mon-caption">{{ $detail->timeline->completed_count }}/{{ $detail->timeline->total_count }} tahap</span>
            </div>

            {{-- Progress ringkas --}}
            <div class="flex items-center gap-2 mb-3">
                <div class="mon-progress flex-1" role="progressbar"
                     aria-valuenow="{{ $detail->progress_pct }}" aria-valuemin="0" aria-valuemax="100">
                    <div class="mon-progress-fill" style="width:{{ $detail->progress_pct }}%"></div>
                </div>
                <span class="mon-pct">{{ $detail->progress_pct }}%</span>
            </div>

            @include('livewire.monitoring.unit-timeline', ['timeline' => $detail->timeline])
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             SECTION 3 — INFORMASI OPERASIONAL  (grouping bisnis, bukan tabel DB)
        ══════════════════════════════════════════════════════════════ --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

            {{-- Pengiriman --}}
            <section class="jss-detail-section" aria-label="Informasi pengiriman">
                <div class="jss-detail-section-head">
                    <span class="jss-detail-section-title">Pengiriman</span>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Nomor Resi</dt>
                    <dd class="mon-table font-semibold" style="color:var(--mon-navy-600)">{{ $detail->shipment_code }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Nomor SPPB</dt>
                    <dd class="mon-table">{{ $detail->doc_number }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Customer</dt>
                    <dd class="mon-table">{{ $detail->customer_name }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Moda</dt>
                    <dd class="mon-table">{{ $modeLabel }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Prioritas</dt>
                    <dd class="mon-table">{{ $priorityLabel }}</dd>
                </dl>
            </section>

            {{-- Perjalanan --}}
            <section class="jss-detail-section" aria-label="Informasi perjalanan">
                <div class="jss-detail-section-head">
                    <span class="jss-detail-section-title">Perjalanan</span>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Voyage</dt>
                    <dd class="mon-table">{{ $admin->voyage_no ?? '—' }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Kapal</dt>
                    <dd class="mon-table">{{ $admin->vessel_name ?? '—' }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">ETD</dt>
                    <dd class="mon-table">{{ $admin->etd?->translatedFormat('d M Y') ?? '—' }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">ETA</dt>
                    <dd class="mon-table">{{ $admin->eta?->translatedFormat('d M Y') ?? '—' }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Rute</dt>
                    <dd class="mon-table">{{ $routeTitle }}</dd>
                </dl>
            </section>

            {{-- Unit --}}
            <section class="jss-detail-section" aria-label="Informasi unit">
                <div class="jss-detail-section-head">
                    <span class="jss-detail-section-title">Unit</span>
                </div>
                <dl class="grid grid-cols-2 gap-x-4 gap-y-2">
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Nomor Unit</dt>
                    <dd class="mon-table font-semibold">{{ $detail->unit_reg_no }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Model</dt>
                    <dd class="mon-table">{{ $detail->unit_model_no ?? '—' }}</dd>
                    <dt class="mon-foot" style="color:var(--mon-neutral-400)">Warna</dt>
                    <dd class="mon-table">{{ $detail->unit_color }}</dd>
                </dl>

                {{-- Data teknis disembunyikan (tidak memenuhi layar) --}}
                <details class="mt-3">
                    <summary class="mon-caption cursor-pointer">Data teknis</summary>
                    <dl class="grid grid-cols-2 gap-x-4 gap-y-2 mt-2">
                        <dt class="mon-foot" style="color:var(--mon-neutral-400)">Chassis</dt>
                        <dd class="mon-table">{{ $detail->unit_chassis_no ?? '—' }}</dd>
                        <dt class="mon-foot" style="color:var(--mon-neutral-400)">Engine</dt>
                        <dd class="mon-table">{{ $detail->unit_engine_no ?? '—' }}</dd>
                        <dt class="mon-foot" style="color:var(--mon-neutral-400)">SJKB</dt>
                        <dd class="mon-table">{{ $detail->unit_sjkb_no ?? '—' }}</dd>
                        <dt class="mon-foot" style="color:var(--mon-neutral-400)">Container</dt>
                        <dd class="mon-table">{{ $detail->container_display ?? '—' }}</dd>
                    </dl>
                </details>
            </section>
        </div>

        {{-- ══════════════════════════════════════════════════════════════
             SECTION 4 — PEMERIKSAAN UNIT  (Unit Inspection Engine)
        ══════════════════════════════════════════════════════════════ --}}
        <section class="jss-detail-section" aria-label="Pemeriksaan unit">
            <div class="jss-detail-section-head">
                <span class="jss-detail-section-title">Pemeriksaan Unit</span>
                @if ($detail->inspection->total_stages > 0)
                    <span class="mon-caption">{{ $detail->inspection->submitted_stages }}/{{ $detail->inspection->total_stages }} tahap</span>
                @endif
            </div>

            @if (!empty($detail->inspection->stages))
                <div class="flex flex-col gap-2">
                    @foreach ($detail->inspection->stages as $ins)
                        @php
                            if (($ins->ng_count ?? 0) > 0) {
                                $insLabel = 'Temuan · ' . $ins->ng_count; $insClass = 'mon-badge-danger';
                            } elseif ($ins->is_submitted) {
                                $insLabel = 'Selesai'; $insClass = 'mon-badge-success';
                            } else {
                                $insLabel = 'Belum'; $insClass = 'mon-badge-neutral';
                            }
                        @endphp
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex flex-col">
                                <span class="mon-table">{{ $ins->stage_label }}</span>
                                @if ($ins->checked_at || $ins->inspector_name)
                                    <span class="mon-foot" style="color:var(--mon-neutral-400)">
                                        {{ $ins->checked_at?->translatedFormat('d M Y') }}{{ $ins->inspector_name ? ' · ' . $ins->inspector_name : '' }}
                                    </span>
                                @endif
                            </div>
                            <span class="mon-badge {{ $insClass }}">{{ $insLabel }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="jss-detail-empty-val">Belum ada pemeriksaan tercatat.</p>
            @endif
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             SECTION 5 — RIWAYAT AKTIVITAS  (kronologis, bahasa operasional)
        ══════════════════════════════════════════════════════════════ --}}
        <section id="riwayat" class="jss-detail-section" aria-label="Riwayat aktivitas">
            <div class="jss-detail-section-head">
                <span class="jss-detail-section-title">Riwayat Aktivitas</span>
            </div>

            @if ($activity->isNotEmpty())
                <ol class="flex flex-col gap-3">
                    @foreach ($activity as $act)
                        <li class="flex flex-col">
                            <span class="mon-foot" style="color:var(--mon-neutral-400)">
                                {{ $act->tracked_at->translatedFormat('d M Y') }} · {{ $act->tracked_at->format('H:i') }}
                            </span>
                            <span class="mon-table">Tahap diperbarui menjadi <span class="font-semibold">{{ $act->label }}</span></span>
                            @if (!empty($act->note))
                                <span class="mon-foot" style="color:var(--mon-neutral-400)">{{ $act->note }}</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            @else
                <p class="jss-detail-empty-val">Belum ada aktivitas tercatat.</p>
            @endif
        </section>

        {{-- ══════════════════════════════════════════════════════════════
             SECTION 6 — DOKUMEN  (paling akhir)
        ══════════════════════════════════════════════════════════════ --}}
        <section id="dokumen" class="jss-detail-section" aria-label="Dokumen">
            <div class="jss-detail-section-head">
                <span class="jss-detail-section-title">Dokumen</span>
            </div>

            @php
                // v1.1 audit: section Dokumen HANYA berisi dokumen — Resi + SPPB
                // + Lampiran. Voyage & Customer (juga ada di deep_links) BUKAN
                // dokumen; keduanya sudah tampil di Informasi Operasional
                // (Perjalanan / Pengiriman), jadi dikeluarkan dari sini.
                $docLinks = collect($detail->deep_links)->filter(
                    fn ($l) => in_array($l->icon, ['heroicon-o-document-text', 'heroicon-o-paper-clip'], true)
                );
            @endphp
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('shipments.resi', ['shipment' => $detail->shipment_id]) }}" target="_blank" class="mon-deeplink">
                    Resi
                    <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                </a>
                @foreach ($docLinks as $link)
                    <a href="{{ $link->url }}" class="mon-deeplink" title="{{ $link->description ?? $link->label }}">
                        {{ $link->label }}
                        <x-heroicon-o-arrow-top-right-on-square class="w-3.5 h-3.5" />
                    </a>
                @endforeach
            </div>
        </section>

    </div>
</x-filament-panels::page>
