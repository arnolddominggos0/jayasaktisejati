<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Builder;

trait BranchScoped
{
    protected static function applyBranchScope(Builder $query): Builder
    {
        if (app()->bound('currentBranchId') && app('currentBranchId')) {
            $query->where('branch_id', app('currentBranchId'));
        }

        return $query;
    }
}
