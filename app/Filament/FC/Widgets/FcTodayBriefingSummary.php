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

        $session = BriefingSession::query()
            ->whereDate('date', today())
            ->where('depot_id', $depotId)
            ->with(['attendances', 'stockApdChecks'])
            ->first();

        if (! $session) {
            return $this->emptyState();
        }

        /** @var \Illuminate\Database\Eloquent\Collection $attendances */
        $attendances = $session->attendances;

        $hadir        = $attendances->where('attendance_status', 'present')->count();
        $tidakHadir   = $attendances->where('attendance_status', '!=', 'present')->count();
        $siapKerja    = $attendances->filter(fn ($a) => $a->final_mp_status === 'Siap Kerja')->count();
        $perluRecheck = $attendances->filter(fn ($a) => in_array($a->final_mp_status, [
            'Perlu Pemeriksaan Ulang', 'APD Tidak Lengkap', 'Istirahat 30 Menit',
        ], true))->count();
        $tidakFit     = $attendances->filter(fn ($a) => $a->final_mp_status === 'Tidak Fit')->count();

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

        return [
            'session'       => $session,
            'has_session'   => true,
            'create_url'    => BriefingSessionResource::getUrl('create'),
            'view_url'      => BriefingSessionResource::getUrl('view', ['record' => $session->id]),
            'status'        => $status,
            'target'        => $target,
            'hadir'         => $hadir,
            'tidak_hadir'   => $tidakHadir,
            'siap_kerja'    => $siapKerja,
            'perlu_recheck' => $perluRecheck,
            'tidak_fit'     => $tidakFit,
            'apd_total'     => $apdTotal,
            'apd_kurang'    => $apdKurang,
            // Compact readiness flag — matches FcOperationalReadiness logic
            'is_ready'      => $status?->value === 'cleared',
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
