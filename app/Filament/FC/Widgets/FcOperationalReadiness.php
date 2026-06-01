<?php

namespace App\Filament\FC\Widgets;

use App\Enums\MPCheckStatus;
use App\Models\BriefingSession;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;
use Illuminate\Support\Carbon;

class FcOperationalReadiness extends Widget
{
    protected static string $view = 'filament.fc.widgets.operational-readiness';
    protected static ?string $heading = 'Kesiapan Operasional Hari Ini';
    protected static ?string $pollingInterval = '30s';
    protected int|string|array $columnSpan = 'full';

    public function getViewData(): array
    {
        $depotId = $this->getDepotId();
        $today = Carbon::today();

        $session = BriefingSession::with(['attendances.manpower', 'stockApdChecks'])
            ->whereDate('date', $today)
            ->when($depotId, fn ($q) => $q->where('depot_id', $depotId))
            ->first();

        if (! $session) {
            return $this->emptyData();
        }

        $attendances = $session->attendances;
        $hadir = $attendances->where('attendance_status', 'present')->count();
        $fit = $attendances->where('fit_status', 'FIT')->count();
        $unfit = $attendances->filter(fn ($a) => $a->fit_status !== 'FIT' && $a->attendance_status === 'present')->count();
        $recheck = $attendances->where('recheck_required', true)->count();
        $kebutuhan = (int) ($session->summary_headcount ?? 0);

        $apdChecks = $session->stockApdChecks;
        $apdTotal = $apdChecks->count();
        $apdKurang = $apdChecks->filter(fn ($c) => $c->status === 'kurang' || ($c->stock_available !== null && $c->required_quantity !== null && $c->stock_available < $c->required_quantity))->count();

        $status = $session->mp_check_status;
        $statusLabel = $status instanceof MPCheckStatus ? $status->label() : ucfirst((string) $status);
        $statusColor = $status instanceof MPCheckStatus ? $status->color() : 'gray';

        $isReady = in_array($status?->value, ['cleared', 'approved'], true);

        $state = $isReady ? 'ready' : 'not_ready';

        $issues = [];

        if ($hadir < $kebutuhan) {
            $issues[] = 'Kekurangan MP: hadir ' . $hadir . ' dari ' . $kebutuhan . ' yang dibutuhkan';
        }

        if ($unfit > 0) {
            $issues[] = $unfit . ' MP tidak fit untuk bekerja';
        }

        if ($recheck > 0) {
            $issues[] = $recheck . ' MP menunggu pemeriksaan ulang';
        }

        if ($apdKurang > 0) {
            $items = $apdChecks->filter(fn ($c) => $c->status === 'kurang' || ($c->stock_available !== null && $c->required_quantity !== null && $c->stock_available < $c->required_quantity))
                ->pluck('ppe_type')
                ->join(', ');
            $issues[] = 'APD kurang: ' . $items;
        }

        if (! $isReady && $status?->value !== null) {
            $issues[] = 'Status pemeriksaan: ' . $statusLabel;
        }

        return [
            'session' => $session,
            'state' => $state,
            'statusLabel' => $statusLabel,
            'statusColor' => $statusColor,
            'isReady' => $isReady,
            'kebutuhan' => $kebutuhan,
            'hadir' => $hadir,
            'fit' => $fit,
            'unfit' => $unfit,
            'recheck' => $recheck,
            'apdTotal' => $apdTotal,
            'apdKurang' => $apdKurang,
            'issues' => $issues,
            'mpPercent' => $kebutuhan > 0 ? min(100, (int) round(($hadir / $kebutuhan) * 100)) : 0,
        ];
    }

   protected function getDepotId(): ?int
   {
   	 $user = Filament::auth()->user();

    	if (! $user) {
            return null;
   	 }

    $depotId = app()->bound('scope.depot_id')
        ? app('scope.depot_id')
        : ($user->scope_unit_type === 'depot'
            ? $user->scope_unit_id
            : null);

     if (! $depotId) {
        $depotId = \App\Models\Depot::where(
            'coordinator_user_id',
            $user->id
        )->value('id');
    }

    return $depotId;
   }

    protected function emptyData(): array
    {
        return [
            'session' => null,
            'state' => 'no_session',
            'statusLabel' => 'Belum Ada Briefing',
            'statusColor' => 'gray',
            'isReady' => false,
            'kebutuhan' => 0,
            'hadir' => 0,
            'fit' => 0,
            'unfit' => 0,
            'recheck' => 0,
            'apdTotal' => 0,
            'apdKurang' => 0,
            'issues' => ['Belum ada sesi briefing hari ini'],
            'mpPercent' => 0,
        ];
    }
}
