<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JslMediaAsset extends Model
{
    use SoftDeletes;

    protected $table = 'jsl_media_assets';

    protected $fillable = [
        'disk',
        'file_path',
        'file_name',
        'mime_type',
        'size_bytes',
        'width',
        'height',
        'variant_thumbnail_path',
        'variant_medium_path',
        'variant_large_path',
    ];

    public function url(?string $variant = null): ?string
    {
        $path = match ($variant) {
            'thumbnail' => $this->variant_thumbnail_path,
            'medium' => $this->variant_medium_path,
            'large' => $this->variant_large_path,
            default => $this->file_path,
        };

        if (! $path) {
            return null;
        }

        return \Storage::disk($this->disk)->url($path);
    }
}
