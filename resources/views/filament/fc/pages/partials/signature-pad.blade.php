<div
    x-data="{
        drawing: false,
        ctx: null,
        empty: true,
        init() {
            const canvas = this.$refs.canvas;
            canvas.width = canvas.parentElement.clientWidth;
            canvas.height = 180;
            this.ctx = canvas.getContext('2d');
            this.ctx.strokeStyle = '#111827';
            this.ctx.lineWidth = 2;
            this.ctx.lineJoin = 'round';
            this.ctx.lineCap = 'round';
        },
        pointFromEvent(e) {
            const rect = this.$refs.canvas.getBoundingClientRect();
            const t = e.touches && e.touches.length ? e.touches[0] : e;
            return { x: t.clientX - rect.left, y: t.clientY - rect.top };
        },
        start(e) {
            this.drawing = true;
            const p = this.pointFromEvent(e);
            this.ctx.beginPath();
            this.ctx.moveTo(p.x, p.y);
        },
        draw(e) {
            if (! this.drawing) return;
            const p = this.pointFromEvent(e);
            this.ctx.lineTo(p.x, p.y);
            this.ctx.stroke();
            this.empty = false;
        },
        end() {
            if (! this.drawing) return;
            this.drawing = false;
            if (! this.empty) {
                $wire.set('data.signature_data', this.$refs.canvas.toDataURL('image/png'));
            }
        },
        clear() {
            this.ctx.clearRect(0, 0, this.$refs.canvas.width, this.$refs.canvas.height);
            this.empty = true;
            $wire.set('data.signature_data', null);
        },
    }"
>
    <canvas
        x-ref="canvas"
        x-on:mousedown="start($event)"
        x-on:mousemove="draw($event)"
        x-on:mouseup="end()"
        x-on:mouseleave="end()"
        x-on:touchstart.prevent="start($event)"
        x-on:touchmove.prevent="draw($event)"
        x-on:touchend.prevent="end()"
        class="w-full rounded-lg border border-gray-300 bg-white dark:border-gray-600"
        style="touch-action: none;"
    ></canvas>

    <div class="mt-2 flex justify-end">
        <button
            type="button"
            x-on:click="clear()"
            class="fi-btn inline-flex items-center gap-1 rounded-lg border border-gray-300 px-3 py-1.5 text-xs font-semibold text-gray-600 hover:bg-gray-50 dark:border-gray-600 dark:text-gray-300 dark:hover:bg-gray-800"
        >
            Bersihkan
        </button>
    </div>
</div>
