<?php if (isset($component)) { $__componentOriginal166a02a7c5ef5a9331faf66fa665c256 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256 = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-panels::components.page.index','data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-panels::page'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
  <div class="space-y-6">
    <?php if (isset($component)) { $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'filament-widgets::components.widgets','data' => ['widgets' => [
        \App\Filament\Pages\Dashboard\Widgets\KpiOverview::class,
        \App\Filament\Pages\Dashboard\Widgets\ShipmentsTrendChart::class,
        \App\Filament\Pages\Dashboard\Widgets\ShipmentsByStatusChart::class,
        // \App\Filament\Pages\Dashboard\Widgets\LeadTimeCustomerWidget::class,
        \App\Filament\Pages\Dashboard\Widgets\TrackingActivityTable::class,
        \App\Filament\Pages\Dashboard\Widgets\TodayManpowerWidget::class,
        \App\Filament\Pages\Dashboard\Widgets\ActiveArmadaWidget::class,
      ],'columns' => ['sm'=>1,'xl'=>3]]] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('filament-widgets::widgets'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['widgets' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute([
        \App\Filament\Pages\Dashboard\Widgets\KpiOverview::class,
        \App\Filament\Pages\Dashboard\Widgets\ShipmentsTrendChart::class,
        \App\Filament\Pages\Dashboard\Widgets\ShipmentsByStatusChart::class,
        // \App\Filament\Pages\Dashboard\Widgets\LeadTimeCustomerWidget::class,
        \App\Filament\Pages\Dashboard\Widgets\TrackingActivityTable::class,
        \App\Filament\Pages\Dashboard\Widgets\TodayManpowerWidget::class,
        \App\Filament\Pages\Dashboard\Widgets\ActiveArmadaWidget::class,
      ]),'columns' => \Illuminate\View\Compilers\BladeCompiler::sanitizeComponentAttribute(['sm'=>1,'xl'=>3])]); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $attributes = $__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__attributesOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d)): ?>
<?php $component = $__componentOriginal7259e9ea993f43cfa75aaa166dfee38d; ?>
<?php unset($__componentOriginal7259e9ea993f43cfa75aaa166dfee38d); ?>
<?php endif; ?>
  </div>
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $attributes = $__attributesOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__attributesOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256)): ?>
<?php $component = $__componentOriginal166a02a7c5ef5a9331faf66fa665c256; ?>
<?php unset($__componentOriginal166a02a7c5ef5a9331faf66fa665c256); ?>
<?php endif; ?>
<?php /**PATH D:\Jayasakti\jss_dashboard\resources\views/filament/pages/dashboard-home.blade.php ENDPATH**/ ?>