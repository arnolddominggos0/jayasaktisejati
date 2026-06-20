<x-filament-panels::page>

    {{-- Unit Info Header --}}
    <x-filament::section>
        <x-slot name="heading">Informasi Unit</x-slot>

        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Model</p>
                <p class="mt-1 font-semibold text-gray-900 dark:text-white">{{ $this->inspectedUnit->model_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Chassis No</p>
                <p class="mt-1 font-mono font-bold text-gray-900 dark:text-white">{{ $this->inspectedUnit->chassis_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Engine No</p>
                <p class="mt-1 font-mono text-gray-900 dark:text-white">{{ $this->inspectedUnit->engine_no ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Warna</p>
                <p class="mt-1 text-gray-900 dark:text-white">{{ $this->inspectedUnit->color ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">DO Number</p>
                <p class="mt-1 text-gray-900 dark:text-white">{{ $this->inspectedUnit->do_number ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wide">Stage</p>
                <p class="mt-1 font-medium text-primary-600 dark:text-primary-400">{{ $this->inspection->stage_label }}</p>
            </div>
        </div>

        @if ($this->isReadOnly)
            {{-- ── Evidence Block ──────────────────────────────────────────────── --}}
            <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800 overflow-hidden">

                {{-- Metadata row --}}
                <div class="flex flex-wrap items-center gap-x-6 gap-y-1 px-4 py-3 text-sm">
                    <span class="text-gray-700 dark:text-gray-300">
                        Status:
                        <strong class="{{ $this->inspection->status === 'passed' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ strtoupper($this->inspection->status) }}
                        </strong>
                    </span>
                    <span class="text-gray-700 dark:text-gray-300">
                        Gate Decision:
                        <strong class="{{ match($this->inspection->gate_decision) {
                            'accept'            => 'text-green-600 dark:text-green-400',
                            'allow_with_remark' => 'text-amber-600 dark:text-amber-400',
                            'return_to_pdc'     => 'text-red-600 dark:text-red-400',
                            default             => 'text-gray-600',
                        } }}">
                            {{ \App\Models\UnitInspection::GATE_LABELS[$this->inspection->gate_decision] ?? '—' }}
                        </strong>
                    </span>
                    @if ($this->inspection->checkedBy)
                        <span class="text-gray-700 dark:text-gray-300">
                            Pemeriksa: <strong>{{ $this->inspection->checkedBy->name }}</strong>
                        </span>
                    @endif
                    @if ($this->inspection->submitted_at)
                        <span class="text-gray-700 dark:text-gray-300">
                            Disubmit: <strong>{{ $this->inspection->submitted_at->format('d M Y H:i') }}</strong>
                        </span>
                    @endif
                    @if ($this->inspection->signed_by)
                        <span class="text-gray-700 dark:text-gray-300">
                            PIC: <strong>{{ $this->inspection->signed_by }}</strong>
                        </span>
                    @endif
                    @if ($this->inspection->signed_at)
                        <span class="text-gray-700 dark:text-gray-300">
                            TTD: <strong>{{ $this->inspection->signed_at->format('d M Y H:i') }}</strong>
                        </span>
                    @endif
                </div>

                {{-- Signature preview --}}
                @if ($this->inspection->signature_path)
                    <div class="border-t border-gray-200 dark:border-gray-700 px-4 py-3">
                        <p class="mb-2 text-xs font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
                            Tanda Tangan Digital
                        </p>
                        <img src="{{ asset('storage/' . $this->inspection->signature_path) }}"
                             alt="Tanda Tangan — {{ $this->inspection->signed_by }}"
                             class="max-h-36 rounded-lg border border-gray-200 bg-white object-contain dark:border-gray-700">
                    </div>
                @endif

            </div>

            {{-- Legacy warning: submitted but no signature --}}
            @if ($this->inspection->submitted_at && ! $this->inspection->signature_path)
                <div class="mt-3 flex items-start gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 dark:border-amber-900/50 dark:bg-amber-900/20">
                    <x-heroicon-o-exclamation-triangle class="mt-0.5 h-4 w-4 shrink-0 text-amber-500 dark:text-amber-400" />
                    <p class="text-xs text-amber-700 dark:text-amber-300">
                        Inspeksi lama tanpa signature ditemukan. Untuk bukti legal yang lengkap, pertimbangkan re-inspeksi pada unit ini.
                    </p>
                </div>
            @endif
        @endif
    </x-filament::section>

    {{-- PDF Download (when evidence has been generated) --}}
    @if ($this->isReadOnly && $this->inspection->pdf_path)
        <div class="flex items-center gap-3 rounded-xl border border-gray-200 bg-white px-5 py-4 shadow-sm dark:border-gray-700 dark:bg-gray-900">
            <x-heroicon-o-document-arrow-down class="h-5 w-5 shrink-0 text-primary-600 dark:text-primary-400" />
            <span class="flex-1 text-sm text-gray-700 dark:text-gray-300">
                Bukti inspeksi telah dibuat.
            </span>
            <a href="{{ asset('storage/' . $this->inspection->pdf_path) }}"
               target="_blank"
               class="inline-flex items-center gap-1.5 rounded-lg bg-primary-600 px-4 py-2 text-sm font-semibold text-white shadow-sm transition-colors hover:bg-primary-700 dark:bg-primary-700 dark:hover:bg-primary-600">
                <x-heroicon-m-arrow-down-tray class="h-4 w-4" />
                Download Check Sheet PDF
            </a>
        </div>
    @endif

    {{-- Inspection Form --}}
    <x-filament-panels::form wire:submit="submit">
        {{ $this->form }}

        @if (! $this->isReadOnly)
            <div class="flex justify-end pt-2">
                <x-filament::button type="submit" size="lg" color="primary" icon="heroicon-m-check-circle">
                    Submit Inspeksi
                </x-filament::button>
            </div>
        @endif
    </x-filament-panels::form>

    <x-filament-actions::modals />
</x-filament-panels::page>
