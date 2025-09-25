<?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
     <?php $__env->slot('heading', null, []); ?> <?php echo e(__('Status Kehadiran MP Hari Ini')); ?> <?php $__env->endSlot(); ?>

    <div class="space-y-3">
        <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $items; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
            <?php
                $badge = match(strtolower($it['status'] ?? '')) {
                    'present' => 'bg-green-100 text-green-700',
                    'leave'   => 'bg-yellow-100 text-yellow-700',
                    'sick'    => 'bg-red-100 text-red-700',
                    default   => 'bg-slate-100 text-slate-600',
                };
            ?>
            <div class="rounded-xl border p-3 flex items-center justify-between">
                <div>
                    <div class="font-medium text-slate-800"><?php echo e($it['name']); ?></div>
                    <div class="text-xs text-slate-500"><?php echo e(strtoupper($it['role'])); ?></div>
                </div>
                <div class="flex items-center gap-3">
                    <span class="px-2 py-0.5 text-xs rounded-full <?php echo e($badge); ?>">
                        <?php echo e(ucfirst($it['status'] ?? '—')); ?>

                    </span>
                    <span class="text-xs text-slate-500"><?php echo e($it['time'] ?? '-'); ?></span>
                </div>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
            <div class="text-slate-500 text-sm">Belum ada data kehadiran hari ini.</div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    </div>
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
<?php /**PATH D:\Jayasakti\jss_dashboard\resources\views/filament/widgets/today-manpower-widget.blade.php ENDPATH**/ ?>