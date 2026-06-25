@php
    $scopeUser   = auth_user();
    if (! $scopeUser) return;
    $scopeRole   = $scopeUser->isSuperAdmin()       ? 'Super Admin'
                 : ($scopeUser->isOfficeAdmin()     ? 'Office Admin'
                 : ($scopeUser->isFieldCoordinator() ? 'Field Coordinator'
                 : ''));
    $scopeLabel  = $scopeUser->isSuperAdmin()
        ? 'Semua Cabang'
        : (\Illuminate\Support\Facades\DB::table('branches')
            ->where('id', $scopeUser->effectiveBranchId())
            ->value('name') ?? '—');
@endphp
@if ($scopeRole)
    <div class="px-3 py-2.5 border-b border-gray-100 dark:border-white/10">
        <p class="text-[11px] font-semibold uppercase tracking-wider text-gray-400 dark:text-gray-500">
            {{ $scopeRole }}
        </p>
        <p class="mt-0.5 text-xs font-medium text-gray-700 dark:text-gray-200">
            {{ $scopeLabel }}
        </p>
    </div>
@endif
