<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JslGalleryItem extends Model
{
    use SoftDeletes;

    protected $table = 'jsl_gallery_items';

    protected $fillable = [
        'media_asset_id',
        'caption',
        'category',
        'caption_en',
        'sort_order',
    ];

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(JslMediaAsset::class, 'media_asset_id');
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }
}
