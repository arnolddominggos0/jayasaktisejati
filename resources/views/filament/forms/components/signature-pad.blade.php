<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">

    @php
        $statePath   = $getStatePath();
        $existingUrl = $field->getExistingSignatureUrl();
        $disabled    = $isDisabled();
        $padId       = 'sig-pad-' . str_replace(['.', '[', ']'], '-', $statePath);
    @endphp

    <div
        id="{{ $padId }}"
        x-data="filamentSignaturePad({
            statePath: @js($statePath),
            existingUrl: @js($existingUrl),
            disabled: @js($disabled)
        })"
        x-init="initCanvas()"
        class="flex flex-col gap-2"
    >

        {{-- ── Canvas area ────────────────────────────────────────────── --}}
        <div
            class="relative overflow-hidden rounded-xl border-2
                   {{ $disabled
                        ? 'border-gray-200 bg-gray-50 dark:border-gray-700 dark:bg-gray-800/40'
                        : 'border-dashed border-gray-300 bg-white hover:border-primary-400 dark:border-gray-600 dark:bg-gray-900 dark:hover:border-primary-500' }}"
            style="height: 180px;"
        >
            <canvas
                x-ref="canvas"
                class="absolute inset-0 h-full w-full rounded-xl"
                :style="disabled ? 'cursor:default' : 'touch-action:none; cursor:crosshair'"
                @pointerdown.prevent="if (!disabled) startDraw($event)"
                @pointermove.prevent="if (!disabled) continueDraw($event)"
                @pointerup="stopDraw()"
                @pointerleave="stopDraw()"
                @pointercancel="stopDraw()"
            ></canvas>

            {{-- Empty-state hint --}}
            <p
                x-show="isEmpty && !disabled"
                x-cloak
                class="pointer-events-none absolute inset-0 flex items-center
                       justify-center select-none text-sm text-gray-400
                       dark:text-gray-600"
            >
                Tanda tangani di area ini
            </p>

            {{-- Disabled overlay --}}
            @if ($disabled)
                <div class="absolute inset-0 flex items-center justify-center">
                    <p class="select-none text-xs text-gray-400 dark:text-gray-600">
                        Tanda tangan tidak dapat diubah.
                    </p>
                </div>
            @endif
        </div>

        {{-- ── Controls (hidden when disabled) ───────────────────────── --}}
        @unless ($disabled)
            <div class="flex items-center justify-between gap-2">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                    Gunakan mouse atau layar sentuh untuk tanda tangan.
                </p>
                <button
                    type="button"
                    x-on:click="clearSignature()"
                    class="inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1.5
                           text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-300
                           transition-colors hover:bg-gray-50
                           dark:text-gray-400 dark:ring-gray-600 dark:hover:bg-gray-800"
                >
                    {{-- Trash icon (inline SVG, no heroicon component dependency) --}}
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                         stroke-width="1.5" stroke="currentColor" class="h-3.5 w-3.5">
                        <path stroke-linecap="round" stroke-linejoin="round"
                              d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107
                                 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244
                                 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456
                                 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114
                                 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18
                                 -.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037
                                 -2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                    </svg>
                    Bersihkan
                </button>
            </div>
        @endunless

    </div>{{-- /x-data --}}

</x-dynamic-component>

{{-- ── Alpine.js component (defined once globally, guarded) ───────────── --}}
@once
<script>
if (typeof window.filamentSignaturePad === 'undefined') {
    window.filamentSignaturePad = function ({ statePath, existingUrl, disabled }) {
        return {
            drawing:  false,
            isEmpty:  !existingUrl,
            disabled: disabled,
            ctx:      null,

            /* ── Initialise canvas after DOM is painted ──────────────── */
            initCanvas() {
                this.$nextTick(() => {
                    const el = this.$refs.canvas;
                    if (!el) return;

                    // Physical pixel dimensions for crisp rendering on HiDPI.
                    const dpr = window.devicePixelRatio || 1;
                    const w   = el.offsetWidth  || 400;
                    const h   = el.offsetHeight || 180;
                    el.width  = w * dpr;
                    el.height = h * dpr;

                    const ctx       = el.getContext('2d');
                    ctx.scale(dpr, dpr);
                    ctx.strokeStyle = '#111827';
                    ctx.lineWidth   = 2;
                    ctx.lineCap     = 'round';
                    ctx.lineJoin    = 'round';
                    this.ctx = ctx;

                    // Pre-load existing signature (edit mode).
                    if (existingUrl) {
                        const img       = new Image();
                        img.crossOrigin = 'anonymous';
                        img.onload = () => {
                            ctx.drawImage(img, 0, 0, w, h);
                            this.isEmpty = false;
                        };
                        img.src = existingUrl;
                    }
                });
            },

            /* ── Pointer helpers ─────────────────────────────────────── */
            pos(e) {
                const el  = this.$refs.canvas;
                const r   = el.getBoundingClientRect();
                const dpr = window.devicePixelRatio || 1;
                const sx  = (el.width  / dpr) / r.width;
                const sy  = (el.height / dpr) / r.height;
                const src = e.touches ? e.touches[0] : e;
                return {
                    x: (src.clientX - r.left) * sx,
                    y: (src.clientY - r.top)  * sy,
                };
            },

            /* ── Drawing ─────────────────────────────────────────────── */
            startDraw(e) {
                this.drawing = true;
                const p = this.pos(e);
                this.ctx.beginPath();
                this.ctx.moveTo(p.x, p.y);
            },

            continueDraw(e) {
                if (!this.drawing) return;
                const p = this.pos(e);
                this.ctx.lineTo(p.x, p.y);
                this.ctx.stroke();
            },

            stopDraw() {
                if (!this.drawing) return;
                this.drawing = false;
                this.isEmpty = false;
                // Push PNG data URL to Livewire state.
                this.$wire.set(statePath, this.$refs.canvas.toDataURL('image/png'));
            },

            /* ── Clear ───────────────────────────────────────────────── */
            clearSignature() {
                const el  = this.$refs.canvas;
                const dpr = window.devicePixelRatio || 1;
                this.ctx.clearRect(0, 0, el.width / dpr, el.height / dpr);
                this.isEmpty = true;
                this.$wire.set(statePath, null);
            },
        };
    };
}
</script>
@endonce
