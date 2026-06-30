<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JslVesselImage extends Model
{
    use SoftDeletes;

    protected $table = 'jsl_vessel_images';

    protected $fillable = [
        'vessel_listing_id',
        'media_asset_id',
        'sort_order',
        'alt_text',
    ];

    public function vesselListing(): BelongsTo
    {
        return $this->belongsTo(JslVesselListing::class, 'vessel_listing_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(JslMediaAsset::class, 'media_asset_id');
    }
}
