<?php if (isset($component)) { $__componentOriginalee08b1367eba38734199cf7829b1d1e9 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginalee08b1367eba38734199cf7829b1d1e9 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament::components.section.index','data' => ['class' => 'w-full col-span-full rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm bg-white dark:bg-gray-900']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament::section'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['class' => 'w-full col-span-full rounded-2xl ring-1 ring-gray-200 dark:ring-gray-800 shadow-sm bg-white dark:bg-gray-900']); ?>
     <?php $__env->slot('heading', null, []); ?> 
        <div class="flex items-center justify-between">
            <span>Aktivitas Terbaru (Tracking)</span>
            <a class="text-sm text-primary-600 hover:underline"
                href="<?php echo e(route('filament.admin.resources.shipments.index')); ?>">
                Lihat semua
            </a>
        </div>
     <?php $__env->endSlot(); ?>

    <div class="max-h-96 overflow-y-auto">
        <div class="divide-y divide-gray-100 dark:divide-gray-800">
            <!--[if BLOCK]><![endif]--><?php $__empty_1 = true; $__currentLoopData = $activities; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $act): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    /** @var \Spatie\Activitylog\Models\Activity $act */
                    $track = $act->subject instanceof \App\Models\ShipmentTrack ? $act->subject : null;
                    $ship = $track?->shipment;
                    $props = $act->properties?->toArray() ?? [];
                    $code = $ship?->code ?? ($props['code'] ?? '-');
                    $user = $act->causer?->name ?? 'Sistem';
                    $event = $act->event;

                    $meta = [
                        'track_created' => ['dibuat', 'bg-emerald-500'],
                        'track_status_changed' => ['status diubah', 'bg-indigo-500'],
                        'track_location_changed' => ['lokasi diubah', 'bg-violet-500'],
                        'track_eta_changed' => ['ETA diubah', 'bg-sky-500'],
                        'track_updated' => ['diperbarui', 'bg-gray-500'],
                        'track_deleted' => ['dihapus', 'bg-red-500'],
                        'track_restored' => ['dipulihkan', 'bg-green-500'],
                    ];
                    [$label, $dot] = $meta[$event] ?? ['diperbarui', 'bg-gray-400'];

                    $initial = \Illuminate\Support\Str::of($user)->trim()->substr(0, 1)->upper();

                    $editUrl = $ship
                        ? \App\Filament\Resources\ShipmentResource::getUrl('edit', ['record' => $ship->getKey()])
                        : route('filament.admin.resources.shipments.index');

                    $to = $props['to'] ?? ($props['status'] ?? null);
                    $from = $props['from'] ?? null;
                    $toLabel = $props['to_label'] ?? ($props['status_label'] ?? null);
                    $fromLabel = $props['from_label'] ?? null;

                    $showStatusBadge = in_array($event, ['track_created', 'track_status_changed'], true) && $to;

                    $badgeKey = \App\Filament\Resources\ShipmentTrackingResource\Widgets\RecentTrackingActivities::badgeColor(
                        $to,
                    );
                    $badgeClass = match ($badgeKey) {
                        'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300',
                        'success' => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300',
                        'warning' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300',
                        'info' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300',
                        default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                    };

                    $changedFields = $props['changed_fields'] ?? [];

                    $ts = \Illuminate\Support\Carbon::parse($act->created_at)->locale('id');
                    $text = $ts->calendar();
                    $full = $ts->translatedFormat('d F Y, H:i');
                ?>

                <div class="flex items-start gap-3 py-2.5 px-4 hover:bg-gray-50 dark:hover:bg-gray-800/30">
                    <span class="mt-2 h-2 w-2 rounded-full <?php echo e($dot); ?>"></span>

                    <div class="flex-1 min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <a href="<?php echo e($editUrl); ?>" target="_blank" rel="noopener"
                                class="font-mono text-xs px-2 py-0.5 rounded border border-gray-200 dark:border-gray-700 text-gray-800 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-800/60">
                                <?php echo e($code); ?>

                            </a>

                            <span class="text-xs text-gray-600 dark:text-gray-400">
                                <?php echo e($label); ?> oleh
                            </span>

                            <span
                                class="inline-flex items-center gap-1.5 pl-1.5 pr-2 py-0.5 rounded-full border border-gray-200 dark:border-gray-700">
                                <span
                                    class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-gray-200 dark:bg-gray-700 text-[10px] text-gray-800 dark:text-gray-200">
                                    <?php echo e($initial); ?>

                                </span>
                                <span
                                    class="text-sm font-medium text-gray-900 dark:text-gray-100"><?php echo e($user); ?></span>
                            </span>

                            <!--[if BLOCK]><![endif]--><?php if($showStatusBadge): ?>
                                <span
                                    class="text-[11px] ml-1 inline-flex items-center gap-1 px-2 py-0.5 rounded-full <?php echo e($badgeClass); ?>">
                                    <?php echo e($toLabel ?? strtoupper((string) $to)); ?>

                                </span>

                                <!--[if BLOCK]><![endif]--><?php if($event === 'track_status_changed' && $fromLabel): ?>
                                    <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                        (dari <?php echo e($fromLabel); ?>)
                                    </span>
                                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php if($event === 'track_location_changed'): ?>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    • Lokasi: <?php echo e($props['from'] ?? '-'); ?> → <?php echo e($props['to'] ?? '-'); ?>

                                </span>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php if($event === 'track_eta_changed'): ?>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    • ETA:
                                    <?php echo e($props['from'] ? \Illuminate\Support\Carbon::parse($props['from'])->locale('id')->translatedFormat('d F Y, H:i') : '-'); ?>

                                    →
                                    <?php echo e($props['to'] ? \Illuminate\Support\Carbon::parse($props['to'])->locale('id')->translatedFormat('d F Y, H:i') : '-'); ?>

                                </span>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->

                            <!--[if BLOCK]><![endif]--><?php if($event === 'track_updated' && !empty($changedFields)): ?>
                                <span class="text-[11px] text-gray-500 dark:text-gray-400">
                                    • Field: <?php echo e(implode(', ', array_slice($changedFields, 0, 6))); ?><!--[if BLOCK]><![endif]--><?php if(count($changedFields) > 6): ?>
                                        , …
                                    <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                                </span>
                            <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
                        </div>

                        
                        <time datetime="<?php echo e($ts->toIso8601String()); ?>" title="<?php echo e($full); ?>"
                            class="text-xs text-gray-500 dark:text-gray-400 mt-1 block">
                            <?php echo e($text); ?>

                        </time>
                    </div>
                </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                    <div class="py-3 px-4 text-sm text-gray-500">Belum ada aktivitas.</div>
                <?php endif; ?><!--[if ENDBLOCK]><![endif]-->
            </div>
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
<?php /**PATH D:\Jaya Sakti\jayasaktisejati\resources\views/filament/widgets/recent-tracking-activities.blade.php ENDPATH**/ ?>