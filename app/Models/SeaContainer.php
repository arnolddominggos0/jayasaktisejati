<?php

namespace App\Models;

use App\Enums\ContainerSize;
use App\Enums\ContainerStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeaContainer extends Model
{
    protected $fillable = [
        'booking_id','size_type','container_no','seal_no','status','gross_weight'
    ];

    protected $casts = [
        'size_type' => ContainerSize::class,
        'status'    => ContainerStatus::class,
    ];

    public function booking(): BelongsTo { return $this->belongsTo(SeaBooking::class, 'booking_id'); }
    public function events(): HasMany { return $this->hasMany(SeaContainerEvent::class, 'container_id'); }
}
