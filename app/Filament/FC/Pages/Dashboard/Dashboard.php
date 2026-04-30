<?php

namespace App\Filament\FC\Pages\Dashboard;

use App\Models\Branch;
use App\Models\Depot;
use Filament\Pages\Page;
use Filament\Facades\Filament;
use Illuminate\Contracts\Support\Htmlable;

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

    /**
     * Get the current branch context for the FC user.
     */
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

    /**
     * Get the current depot context for the FC user.
     */
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
            // Fallback: try to find depot by coordinator_user_id
            $depotId = Depot::where('coordinator_user_id', $user->id)->value('id');
        }

        if (! $depotId) {
            return null;
        }

        return Depot::find($depotId);
    }

    /**
     * Get branch name with safe fallback.
     */
    public function getBranchName(): string
    {
        return $this->getBranchContext()?->name ?? 'Branch tidak diketahui';
    }

    /**
     * Get depot name with safe fallback.
     */
    public function getDepotName(): string
    {
        return $this->getDepotContext()?->name ?? 'Depot tidak diketahui';
    }

    /**
     * Check if user has valid branch context.
     */
    public function hasBranchContext(): bool
    {
        return $this->getBranchContext() !== null;
    }

    /**
     * Check if user has valid depot context.
     */
    public function hasDepotContext(): bool
    {
        return $this->getDepotContext() !== null;
    }
}
