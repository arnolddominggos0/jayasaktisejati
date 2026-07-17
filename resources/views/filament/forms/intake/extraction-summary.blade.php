{{--
    OCR-02 — Extraction Summary (Review → Apply)

    Bagian dari wizard (bukan modal/popup): tampil setelah upload SPPB
    menghasilkan IntakePrefill. Form TIDAK berubah sebelum Office Admin
    menekan [Terapkan ke Formulir]; [Abaikan] membuang envelope.
    Setelah Apply → mode ringkas (§9) dengan [Terapkan ulang] &
    [Lihat Ringkasan]. Voyage hanya hint — tidak pernah di-assign.
--}}
@php
    /** @var \App\Support\Intake\IntakePrefill|null $prefill */
    $livewire = $field->getLivewire();
    $prefill  = $livewire->intakePrefill ?? null;
    $applied  = (bool) ($livewire->intakeApplied ?? false);

    $fmtDate = function (?string $iso) {
        if ($iso === null) return null;
        try { return \Illuminate\Support\Carbon::parse($iso)->translatedFormat('d F Y'); }
        catch (\Throwable) { return $iso; }
    };

    $doc      = $prefill?->document ?? [];
    $parties  = $prefill?->parties ?? [];
    $copy     = $prefill?->copyFields ?? [];
    $hints    = $prefill?->voyageHints ?? [];
    $manifest = $prefill?->manifest ?? ['detected_count' => 0, 'units' => []];
    $warnings = $prefill?->warnings ?? [];

    // OCR-02A: TO:/UP:/Email adalah metadata korespondensi internal SPPB,
    // bukan domain Shipment — tidak ditampilkan di summary.
    // DOMAIN-02: kop dokumen = DEALER (bukan Commercial Customer).
    $rows = array_filter([
        'Nomor'           => $doc['number'] ?? null,
        'Tanggal'         => $fmtDate($doc['date'] ?? null),
        'Dealer'          => $parties['dealer_name'] ?? $parties['customer_text'] ?? null,
        'Pickup Location' => $copy['pickup_location']['value'] ?? null,
        'Destination'     => $copy['destination']['value'] ?? null,
        'Delivery Scope'  => isset($copy['delivery_scope']['value'])
            ? \Illuminate\Support\Str::of($copy['delivery_scope']['value'])->replace('_', ' ')->title()
            : null,
    ], fn ($v) => $v !== null && $v !== '');

    $unitModels = array_values(array_filter(array_map(
        fn ($u) => $u['model'] ?? null,
        $manifest['units'] ?? []
    )));
@endphp

@if ($prefill !== null && ! $prefill->isEmpty())
<div
    x-data="{ expanded: @js(! $applied) }"
    {{-- §6 highlight: registrasi via x-init (script dari morph tidak
         dieksekusi Livewire; Alpine mengevaluasi node morph — pasti jalan). --}}
    x-init="
        if (! window.__intakeHighlight) {
            window.__intakeHighlight = true;
            Livewire.on('intake-prefill-applied', ({ fields }) => {
                (fields ?? []).forEach((path) => {
                    document.querySelectorAll(`[wire\\:model=&quot;${path}&quot;],[wire\\:model\\.live=&quot;${path}&quot;],[wire\\:model\\.blur=&quot;${path}&quot;]`).forEach((el) => {
                        const w = el.closest('.fi-fo-field-wrp') ?? el;
                        w.classList.add('ws-ocr-filled');
                        const clear = () => w.classList.remove('ws-ocr-filled');
                        el.addEventListener('input', clear, { once: true });
                        el.addEventListener('change', clear, { once: true });
                    });
                });
            });
        }
    "
    class="rounded-xl border border-gray-200 bg-white dark:border-white/10 dark:bg-gray-900"
    {{-- key berbeda per mode → morph mengganti node → Alpine re-init
         sehingga `expanded` mengikuti mode terbaru (ringkas pasca-Apply). --}}
    wire:key="intake-extraction-summary-{{ $applied ? 'applied' : 'fresh' }}"
>
    {{-- ── Header / mode ringkas (§9) ─────────────────────────────────── --}}
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 sm:px-6">
        <div class="flex items-center gap-2">
            <svg class="h-5 w-5 text-success-600" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 18a8 8 0 1 0 0-16 8 8 0 0 0 0 16Zm3.857-9.809a.75.75 0 0 0-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 1 0-1.06 1.061l2.5 2.5a.75.75 0 0 0 1.137-.089l4-5.5Z" clip-rule="evenodd"/>
            </svg>
            <span class="text-sm font-semibold text-gray-950 dark:text-white">
                @if ($applied)
                    Hasil ekstraksi telah diterapkan.
                @else
                    Dokumen berhasil dibaca
                @endif
            </span>
        </div>

        <div class="flex items-center gap-2">
            @if ($applied)
                <x-filament::button size="sm" color="gray" outlined
                    wire:click="applyIntakePrefill(true)">
                    Terapkan ulang
                </x-filament::button>
                <x-filament::button size="sm" color="gray"
                    x-on:click="expanded = ! expanded">
                    <span x-text="expanded ? 'Sembunyikan Ringkasan' : 'Lihat Ringkasan'"></span>
                </x-filament::button>
            @endif
        </div>
    </div>

    {{-- ── UX-02: mode ringkas pasca-Apply — identitas komersial + legenda.
         Receiver sengaja tidak ditampilkan (bukan workflow utama Vehicle). --}}
    @if ($applied)
        @php
            $dealerSug     = $prefill->suggestionFor('dealer_id');
            $dealerLabel   = $dealerSug['match'] ?? ($parties['dealer_name'] ?? null);
            $customerLabel = $dealerSug['customer_name'] ?? null;
        @endphp
        <div x-show="! expanded" class="border-t border-gray-200 px-4 py-3 dark:border-white/10 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-1 sm:grid-cols-2">
                @if ($dealerLabel)
                    <div class="flex items-baseline gap-2">
                        <dt class="w-20 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">Dealer</dt>
                        <dd class="text-sm text-gray-950 dark:text-white">{{ $dealerLabel }}</dd>
                    </div>
                @endif
                @if ($customerLabel)
                    <div class="flex items-baseline gap-2">
                        <dt class="w-20 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">Customer</dt>
                        <dd class="text-sm text-gray-950 dark:text-white">{{ $customerLabel }}</dd>
                    </div>
                @endif
            </dl>
            @if ($prefill->unitCount() > 0)
                <p class="mt-1.5 text-sm text-gray-700 dark:text-gray-300">{{ $prefill->unitCount() }} kendaraan berhasil dikenali.</p>
            @endif
            <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                Field bergaris hijau berasal dari hasil ekstraksi dokumen — hilang saat Anda mengubahnya.
            </p>
        </div>
    @endif

    {{-- x-collapse sengaja tidak dipakai: plugin Alpine collapse tidak
         dimuat di halaman form panel — directive yang tak dikenal membuat
         x-show ikut gagal dievaluasi. x-show polos sudah cukup. --}}
    <div x-show="expanded" @if ($applied) x-cloak @endif>
        {{-- ── Field terdeteksi ───────────────────────────────────────── --}}
        <div class="border-t border-gray-200 px-4 py-4 dark:border-white/10 sm:px-6">
            <dl class="grid grid-cols-1 gap-x-8 gap-y-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($rows as $label => $value)
                    <div class="flex items-baseline gap-2">
                        <dt class="w-24 shrink-0 text-xs font-medium text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                        <dd class="text-sm text-gray-950 dark:text-white">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>

        {{-- ── Voyage Hint (§5: hanya hint, tidak pernah auto-assign) ──── --}}
        @if (($hints['vessel_name'] ?? null) !== null)
            <div class="border-t border-gray-200 px-4 py-3 dark:border-white/10 sm:px-6">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Voyage Hint</p>
                <p class="mt-1 text-sm text-gray-950 dark:text-white">
                    {{ $hints['vessel_name'] }}
                    @if (($hints['document_etd'] ?? null) !== null)
                        <span class="text-gray-500 dark:text-gray-400">— ETD dokumen {{ $fmtDate($hints['document_etd']) }}</span>
                    @endif
                </p>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Gunakan sebagai pembanding saat memilih Jadwal Kapal — tidak diisi otomatis.
                </p>
            </div>
        @endif

        {{-- ── Manifest ringkas (§3) ──────────────────────────────────── --}}
        <div class="border-t border-gray-200 px-4 py-3 dark:border-white/10 sm:px-6">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Manifest</p>
            @if (($manifest['detected_count'] ?? 0) > 0)
                <p class="mt-1 text-sm font-medium text-gray-950 dark:text-white">
                    ✓ {{ $manifest['detected_count'] }} unit ditemukan
                    @if ($prefill->claimedUnitCount() !== null)
                        <span class="font-normal text-gray-500 dark:text-gray-400">(dokumen menyatakan total {{ $prefill->claimedUnitCount() }})</span>
                    @endif
                </p>
                <ol class="mt-1 space-y-0.5 text-sm text-gray-700 dark:text-gray-300">
                    @foreach ($unitModels as $i => $model)
                        <li>{{ $i + 1 }}. {{ $model }}</li>
                    @endforeach
                </ol>
                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">
                    Semua unit akan dimasukkan ke daftar kendaraan.
                </p>
            @else
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Tidak ada unit terdeteksi.</p>
            @endif
        </div>

        {{-- ── Warnings — hanya dirender bila ada (OCR-02A) ───────────── --}}
        @if ($warnings !== [])
            <div class="border-t border-gray-200 px-4 py-3 dark:border-white/10 sm:px-6">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Warnings</p>
                <ul class="mt-1 space-y-1">
                    @foreach ($warnings as $warning)
                        <li class="text-sm text-warning-600 dark:text-warning-400">⚠ {{ $warning['message'] }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ── Aksi (hanya sebelum Apply) ─────────────────────────────── --}}
        @unless ($applied)
            <div class="flex flex-wrap items-center gap-3 border-t border-gray-200 px-4 py-4 dark:border-white/10 sm:px-6">
                <x-filament::button color="primary" icon="heroicon-m-arrow-down-on-square"
                    wire:click="applyIntakePrefill">
                    Terapkan ke Formulir
                </x-filament::button>
                <x-filament::button color="gray" outlined
                    wire:click="ignoreIntakePrefill">
                    Abaikan — isi manual
                </x-filament::button>
            </div>
        @endunless
    </div>
</div>

@endif
