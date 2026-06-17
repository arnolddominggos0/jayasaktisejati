<?php

namespace App\Filament\FC\Widgets;

use App\Enums\MPCheckStatus;
use App\Filament\FC\Resources\BriefingSessionResource;
use App\Models\BriefingSession;
use App\Models\Depot;
use Filament\Facades\Filament;
use Filament\Widgets\Widget;

class FcTodayBriefingSummary extends Widget
{
    protected static string $view = 'filament.fc.widgets.today-briefing-summary';

    // Render immediately — no lazy skeleton flash needed on a dedicated list page.
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 'full';

    // -------------------------------------------------------------------------
    // View data
    // -------------------------------------------------------------------------

    public function getViewData(): array
    {
        $user = Filament::auth()->user();

        if (! $user) {
            return $this->emptyState();
        }

        $depotId = $this->resolveDepotId($user);

        if (! $depotId) {
            return $this->emptyState();
        }

        // SC.3B.19: Shipment-based — show the most recent active (non-cleared) briefing
        // for active shipments at this depot. Falls back to any recent session.
        $session = BriefingSession::query()
            ->where('depot_id', $depotId)
            ->whereNotNull('shipment_id')
            ->whereIn('mp_check_status', [
                MPCheckStatus::Draft->value,
                MPCheckStatus::OnCheck->value,
                MPCheckStatus::WaitingAction->value,
            ])
            ->with(['attendances', 'stockApdChecks', 'shipment:id,code'])
            ->latest()
            ->first();

        // Fallback: show most recent session (including cleared) so the widget
        // never goes blank once there's at least one session today.
        $session ??= BriefingSession::query()
            ->where('depot_id', $depotId)
            ->whereDate('date', today())
            ->with(['attendances', 'stockApdChecks', 'shipment:id,code'])
            ->latest()
            ->first();

        if (! $session) {
            return $this->emptyState();
        }

        /** @var \Illuminate\Database\Eloquent\Collection $attendances */
        $attendances = $session->attendances;

        $hadir        = $attendances->where('attendance_status', 'present')->count();
        $tidakHadir   = $attendances->where('attendance_status', '!=', 'present')->count();
        // MP FIT: fit_status = 'FIT' (present + recheck yang lulus)
        $fitCount     = $attendances->where('fit_status', 'FIT')->count();
        $perluRecheck = $attendances->where('recheck_required', true)->count();
        $tidakFit     = $attendances->where('fit_status', 'UNFIT')->count();

        $apdChecks = $session->stockApdChecks;
        $apdTotal  = $apdChecks->count();
        $apdKurang = $apdChecks->filter(fn ($c) => $c->status === 'kurang'
            || ($c->stock_available !== null && $c->required_quantity !== null
                && $c->stock_available < $c->required_quantity)
        )->count();

        $status = $session->mp_check_status instanceof MPCheckStatus
            ? $session->mp_check_status
            : MPCheckStatus::tryFrom((string) $session->mp_check_status);

        $target = (int) $session->summary_headcount;

        // Status kesiapan berdasarkan jumlah MP FIT vs kebutuhan
        $isReady = $fitCount >= $target;

        return [
            'session'       => $session,
            'has_session'   => true,
            'create_url'    => BriefingSessionResource::getUrl('create'),
            'view_url'      => BriefingSessionResource::getUrl('view', ['record' => $session->id]),
            'status'        => $status,
            'target'        => $target,
            'hadir'         => $hadir,
            'tidak_hadir'   => $tidakHadir,
            'fit_count'     => $fitCount,
            'perlu_recheck' => $perluRecheck,
            'tidak_fit'     => $tidakFit,
            'apd_total'     => $apdTotal,
            'apd_kurang'    => $apdKurang,
            // Status readiness: FIT >= target (bukan mp_check_status cleared)
            'is_ready'      => $isReady,
        ];
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    protected function resolveDepotId(mixed $user): ?int
    {
        if (app()->bound('scope.depot_id') && app('scope.depot_id') !== null) {
            return (int) app('scope.depot_id');
        }

        if ($user->scope_unit_type === 'depot' && $user->scope_unit_id) {
            return (int) $user->scope_unit_id;
        }

        $raw = Depot::where('coordinator_user_id', $user->id)->value('id');

        return $raw ? (int) $raw : null;
    }

    protected function emptyState(): array
    {
        return [
            'session'     => null,
            'has_session' => false,
            'create_url'  => BriefingSessionResource::getUrl('create'),
        ];
    }
}
