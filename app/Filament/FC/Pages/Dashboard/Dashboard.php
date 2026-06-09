<?php

namespace App\Filament\FC\Pages\Dashboard;

use App\Enums\MPCheckStatus;
use App\Enums\ShipmentStatus;
use App\Models\Branch;
use App\Models\BriefingSession;
use App\Models\Depot;
use App\Models\Shipment;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class Dashboard extends Page
{
    protected static ?string $slug = 'dashboard';

    protected static ?string $navigationIcon  = 'heroicon-m-home';
    protected static ?string $navigationLabel = 'Dashboard';
    protected static ?string $navigationGroup = 'Operasional Lapangan';
    protected static ?int    $navigationSort  = 1;

    protected static ?string $title = 'Dashboard';

    protected static string $view = 'filament.fc.pages.dashboard';

    public static function canAccess(): bool
    {
        return Filament::auth()->user()?->hasRole('field_coordinator') ?? false;
    }

    public function getTitle(): string|Htmlable
    {
        return static::$title ?? 'Dashboard';
    }

    public function getBranchContext(): ?Branch
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return null;
        }

        $branchId = app()->bound('scope.branch_id')
            ? app('scope.branch_id')
            : ($user->effectiveBranchId() ?? null);

        if (! $branchId) {
            return null;
        }

        return Branch::find($branchId);
    }

    public function getDepotContext(): ?Depot
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return null;
        }

        $depotId = app()->bound('scope.depot_id')
            ? app('scope.depot_id')
            : ($user->scope_unit_type === 'depot' ? $user->scope_unit_id : null);

        if (! $depotId) {
            $depotId = Depot::where('coordinator_user_id', $user->id)->value('id');
        }

        if (! $depotId) {
            return null;
        }

        return Depot::find($depotId);
    }

    public function getBranchName(): string
    {
        return $this->getBranchContext()?->name ?? 'Branch tidak diketahui';
    }

    public function getDepotName(): string
    {
        return $this->getDepotContext()?->name ?? 'Depot tidak diketahui';
    }

    public function hasBranchContext(): bool
    {
        return $this->getBranchContext() !== null;
    }

    public function hasDepotContext(): bool
    {
        return $this->getDepotContext() !== null;
    }

    public function getUrgencyCount(): int
    {
        $user = Filament::auth()->user();
        if (! $user) {
            return 0;
        }

        $branchId = app()->bound('scope.branch_id')
            ? app('scope.branch_id')
            : ($user->effectiveBranchId() ?? null);
        $depotId = app()->bound('scope.depot_id')
            ? app('scope.depot_id')
            : null;

        return Shipment::query()
            ->where('mode', 'sea')
            ->whereNotIn('status', [ShipmentStatus::Delivered->value, ShipmentStatus::Cancelled->value])
            ->when($branchId, fn (Builder $q) => $q->where(fn ($w) => $w->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->when($depotId, fn (Builder $q) => $q->where(function ($w) use ($depotId, $user) {
                $w->where('assigned_depot_id', $depotId)
                    ->orWhere('coordinator_id', $user->id);
            }), fn (Builder $q) => $q->where('coordinator_id', $user->id))
            ->where(function (Builder $q) {
                $q->where('priority', 'urgent')
                    ->orWhere('status', ShipmentStatus::Hold->value)
                    ->orWhere(function (Builder $q2) {
                        $q2->whereNotNull('eta')
                            ->where('eta', '<=', now()->addDay());
                    });
            })
            ->count();
    }

    public function getTodayBriefingSession(): ?BriefingSession
    {
        $depotId = $this->getDepotContext()?->id;

        if (! $depotId) {
            return null;
        }

        return BriefingSession::withCount([
            'attendances as hadir_count' => fn ($q) => $q->where('attendance_status', 'present'),
        ])
        ->whereDate('date', Carbon::today())
        ->where('depot_id', $depotId)
        ->first();
    }

    public function getOperationalReadinessBadge(): array
    {
        $session = $this->getTodayBriefingSession();

        if (! $session) {
            return ['label' => 'Belum Ada Briefing', 'color' => 'gray', 'icon' => 'heroicon-m-clock'];
        }

        $status = $session->mp_check_status;
        $isReady = $status?->value === 'cleared';

        return [
            'label' => $isReady ? 'Operasional: SIAP' : 'Operasional: BELUM SIAP',
            'color' => $isReady ? 'success' : 'danger',
            'icon' => $isReady ? 'heroicon-m-check-circle' : 'heroicon-m-exclamation-triangle',
        ];
    }
}
