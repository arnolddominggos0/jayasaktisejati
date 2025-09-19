<?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => ['class' => 'bg-white dark:bg-gray-900']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'bg-white dark:bg-gray-900']); ?>
     <?php $__env->slot('heading', null, []); ?> Lead Time Evaluation (Sea) per Customer <?php $__env->endSlot(); ?>

    
    <div class="mb-4">
        <form wire:submit.prevent="applyForm">
            <?php echo e($this->form); ?>

        </form>
    </div>

    <?php
        $cards = [
            ['key' => 'dwelling', 'title' => 'Dwelling time'],
            ['key' => 'sailing',  'title' => 'Sailing time'],
            ['key' => 'dooring',  'title' => 'Dooring time'],
            ['key' => 'total',    'title' => 'Total L/T'],
        ];
    ?>

    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6">
        <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $cards; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $c): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <?php
                $ok  = (int) ($stats[$c['key']]['ok'] ?? 0);
                $ng  = (int) ($stats[$c['key']]['ng'] ?? 0);
                $sum = $ok + $ng;
                $okPct = $sum ? round($ok / $sum * 100) : 0;
                $ngPct = $sum ? 100 - $okPct : 0;
            ?>

            <div class="p-4 rounded-xl border border-gray-100 dark:border-gray-800">
                <div class="text-sm font-medium mb-2"><?php echo e($c['title']); ?></div>

                <!--[if BLOCK]><![endif]--><?php if($sum === 0): ?>
                    <div class="h-48 flex items-center justify-center text-sm text-gray-500">
                        Tidak ada data pada rentang ini
                    </div>
                <?php else: ?>
                    <div class="h-48" wire:ignore
                         x-data="ltDonut({ ok: <?php echo e($ok); ?>, ng: <?php echo e($ng); ?> })"
                         x-init="init()"
                         x-on:resize.window.debounce.200ms="redraw()">
                        <canvas x-ref="cv"></canvas>
                    </div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                <div class="flex items-center justify-center gap-4 mt-3 text-xs">
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full" style="background:#22C55E"></span>
                        OK: <?php echo e($ok); ?> (<?php echo e($okPct); ?>%)
                    </span>
                    <span class="inline-flex items-center gap-1">
                        <span class="h-2 w-2 rounded-full" style="background:#EF4444"></span>
                        NG: <?php echo e($ng); ?> (<?php echo e($ngPct); ?>%)
                    </span>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
    </div>

        <?php
        $__scriptKey = '1080187807-0';
        ob_start();
    ?>
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('ltDonut', (cfg) => ({
            chart: null,
            init() {
                this.$nextTick(() => this.redraw());
            },
            redraw() {
                const ctx = this.$refs.cv?.getContext('2d');
                if (!ctx || typeof Chart === 'undefined') return;
                if (this.chart) this.chart.destroy();

                this.chart = new Chart(ctx, {
                    type: 'doughnut',
                    data: {
                        labels: ['OK','NG'],
                        datasets: [{
                            data: [cfg.ok, cfg.ng],
                            backgroundColor: ['#22C55E','#EF4444'],
                            borderWidth: 0,
                        }],
                    },
                    options: {
                        maintainAspectRatio: false,
                        cutout: '62%',
                        plugins: {
                            legend: { display: false },
                            tooltip: { displayColors: false },
                        },
                    },
                });
            },
            destroy() { if (this.chart) this.chart.destroy(); },
        }));
    });
    </script>
        <?php
        $__output = ob_get_clean();

        \Livewire\store($this)->push('scripts', $__output, $__scriptKey)
    ?>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $attributes = $__attributesOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__attributesOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalee08b1367eba38734199cf7829b1d1e9)): ?>
<?php $component = $__componentOriginalee08b1367eba38734199cf7829b1d1e9; ?>
<?php unset($__componentOriginalee08b1367eba38734199cf7829b1d1e9); ?>
<?php endif; ?>
<?php /**PATH D:\Jayasakti\jss_dashboard\resources\views/filament/pages/dashboard/widgets/lead-time-customer.blade.php ENDPATH**/ ?>