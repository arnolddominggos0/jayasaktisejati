<div class="col-span-3">
    <?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => ['class' => 'w-full rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm bg-white dark:bg-gray-900']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm bg-white dark:bg-gray-900']); ?>
         <?php $__env->slot('heading', null, []); ?> 
            <div class="flex items-center justify-between">
                <span>Aktivitas Terbaru</span>
                <a class="text-sm text-primary-600 hover:underline"
                    href="<?php echo e(route('filament.admin.resources.shipments.index')); ?>">
                    Lihat semua
                </a>
            </div>
         <?php $__env->endSlot(); ?>

        <div class="max-h-96 overflow-y-auto">
            <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $groups; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <div class="px-4 pt-3 pb-2 sticky top-0 bg-white/90 dark:bg-gray-900/90 backdrop-blur z-10">
                    <div class="text-xs font-semibold text-gray-700 dark:text-gray-300 tracking-wide">
                        <?php echo e($group['title']); ?>

                    </div>
                </div>

                <div class="divide-y divide-gray-100 dark:divide-gray-800">
                    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $group['items']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $it): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="flex items-start gap-3 py-2.5 px-4 hover:bg-gray-50 dark:hover:bg-gray-800/30">
                            <span class="mt-2 h-2 w-2 rounded-full <?php echo e($it['dotClass']); ?>"></span>

                            <div class="flex-1 min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <a href="<?php echo e($it['editUrl']); ?>" target="_blank" rel="noopener"
                                        class="font-mono text-xs px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/60">
                                        <?php echo e($it['code']); ?>

                                    </a>

                                    <?php if (isset($component)) { $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.icon','data' => ['icon' => ''.e($it['icon']).'','class' => 'h-4 w-4 text-gray-500']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::icon'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['icon' => ''.e($it['icon']).'','class' => 'h-4 w-4 text-gray-500']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $attributes = $__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__attributesOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>
<?php if (isset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950)): ?>
<?php $component = $__componentOriginalbfc641e0710ce04e5fe02876ffc6f950; ?>
<?php unset($__componentOriginalbfc641e0710ce04e5fe02876ffc6f950); ?>
<?php endif; ?>

                                    <span class="text-xs text-gray-600 dark:text-gray-400">
                                        <?php echo e($it['eventLabel']); ?> oleh
                                    </span>

                                    <span
                                        class="inline-flex items-center gap-1.5 pl-1.5 pr-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700">
                                        <span
                                            class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-[10px] text-gray-800 dark:text-gray-200">
                                            <?php echo e($it['initial']); ?>

                                        </span>
                                        <span
                                            class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($it['user']); ?></span>
                                    </span>

                                    <!--[if BLOCK]><![endif]--><?php if($it['showStatus']): ?>
                                        <span
                                            class="text-[11px] ml-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full <?php echo e($it['chipClass']); ?>">
                                            <?php echo e($it['toLabel']); ?>

                                        </span>

                                        <!--[if BLOCK]><![endif]--><?php if($it['fromLabel'] && in_array($it['event'], ['status_changed', 'cancelled', 'uncancelled'], true)): ?>
                                            <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                                (dari <?php echo e($it['fromLabel']); ?>)
                                            </span>
                                        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                                    <!--[if BLOCK]><![endif]--><?php if($it['changedText']): ?>
                                        <span
                                            class="ml-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-800 px-2 py-0.5 rounded-md">
                                            Field: <?php echo e($it['changedText']); ?>

                                        </span>
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </div>

                                <time title="<?php echo e($it['fullTime']); ?>"
                                    class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                                    <?php echo e($it['calendarTime']); ?>

                                </time>
                            </div>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
                </div>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                <div class="py-3 px-4 text-sm text-gray-500">Belum ada aktivitas.</div>
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
</div>
<?php /**PATH D:\Jayasakti\jss_dashboard\resources\views/filament/widgets/recent-shipment-activities.blade.php ENDPATH**/ ?>