<?php
    $order = \App\Enums\TrackStatus::order();
    $last = $getRecord()->latestTrack?->status?->value ?? ($getRecord()->latestTrack?->status ?? null);
    $idx = $last ? array_search(\App\Enums\TrackStatus::from($last), $order, true) : -1;
?>
<div class="min-w-[420px] flex items-center gap-2">
    <!--[if BLOCK]><![endif]--><?php $__currentLoopData = $order; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $i => $step): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
        <div class="flex flex-col items-center w-8">
            <div
                class="w-6 h-6 rounded-full flex items-center justify-center text-[10px]
                <?php echo e($i <= $idx ? 'bg-primary-600 text-white' : 'bg-gray-200 dark:bg-gray-700 text-gray-600 dark:text-gray-300'); ?>">
                <?php echo e($i + 1); ?>

            </div>
        </div>
        <!--[if BLOCK]><![endif]--><?php if($i < count($order) - 1): ?>
            <div class="h-0.5 flex-1 <?php echo e($i < $idx ? 'bg-primary-600' : 'bg-gray-200 dark:bg-gray-700'); ?>"></div>
        <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?><!--[if ENDBLOCK]><![endif]-->
</div>
<?php /**PATH D:\Jaya Sakti\jayasaktisejati\resources\views/tables/columns/tracking-progress.blade.php ENDPATH**/ ?>