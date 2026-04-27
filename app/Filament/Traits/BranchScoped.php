<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    protected static function applyBranchScope(Builder $query): Builder
    {
        if (app()->bound('currentBranchId') && app('currentBranchId')) {
            $query->where(fn ($w) => $w->where('branch_id', app('currentBranchId'))->orWhereNull('branch_id'));
        }

        return $query;
    }
}
