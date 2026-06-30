<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class JslService extends Model
{
    use SoftDeletes;

    protected $table = 'jsl_services';

    protected $fillable = [
        'title',
        'description',
        'title_en',
        'description_en',
        'media_asset_id',
        'is_visible',
        'sort_order',
    ];

    protected $casts = [
        'is_visible' => 'boolean',
    ];

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(JslMediaAsset::class, 'media_asset_id');
    }

    public function scopeVisible($query)
    {
        return $query->where('is_visible', true)->orderBy('sort_order');
    }
}
