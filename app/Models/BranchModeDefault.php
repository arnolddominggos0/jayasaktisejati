<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BranchModeDefault extends Model
{
    protected $fillable = ['branch_id', 'mode', 'outbound_depot_id'];
    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
    public function outboundDepot(): BelongsTo
    {
        return $this->belongsTo(Depot::class, 'outbound_depot_id');
    }
}
