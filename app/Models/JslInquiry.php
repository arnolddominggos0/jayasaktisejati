<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JslInquiry extends Model
{
    use SoftDeletes;

    protected $table = 'jsl_inquiries';

    protected $fillable = [
        'name',
        'company',
        'email',
        'phone',
        'message',
        'vessel_listing_id',
        'consent_given',
        'status',
    ];

    protected $casts = [
        'consent_given' => 'boolean',
    ];

    public function vesselListing(): BelongsTo
    {
        return $this->belongsTo(JslVesselListing::class, 'vessel_listing_id');
    }
}
