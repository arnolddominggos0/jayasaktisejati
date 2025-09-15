<?php

namespace App\Models;

use App\Enums\SeaBookingStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SeaBooking extends Model
{
    protected $fillable = [
        'code','shipping_line_id','voyage_id','ro_no','rc_no','si_no','status','depot_id','notes'
    ];

    protected $casts = [
        'status' => SeaBookingStatus::class,
    ];

    public function shippingLine(): BelongsTo { return $this->belongsTo(ShippingLine::class); }
    public function voyage(): BelongsTo { return $this->belongsTo(Voyage::class); }
    public function depot(): BelongsTo { return $this->belongsTo(Depot::class); }
    public function containers(): HasMany { return $this->hasMany(SeaContainer::class, 'booking_id'); }
}
