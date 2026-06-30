<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class JslVesselListing extends Model
{
    use SoftDeletes;

    protected $table = 'jsl_vessel_listings';

    protected $fillable = [
        'public_ref_code',
        'vessel_type',
        'year_built',
        'flag_registry',
        'gross_tonnage',
        'deadweight',
        'loa_length',
        'beam',
        'draft',
        'engine_power',
        'trading_area',
        'marketing_description',
        'marketing_description_en',
        'real_vessel_name',
        'imo_number',
        'owner_details',
        'certificates',
        'price_commercial_terms',
        'status',
    ];

    protected $casts = [
        'year_built' => 'integer',
        'gross_tonnage' => 'decimal:2',
        'deadweight' => 'decimal:2',
        'loa_length' => 'decimal:2',
        'beam' => 'decimal:2',
        'draft' => 'decimal:2',
    ];

    protected array $sensitiveFields = [
        'real_vessel_name',
        'imo_number',
        'owner_details',
        'certificates',
        'price_commercial_terms',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(JslVesselImage::class, 'vessel_listing_id')->orderBy('sort_order');
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(JslInquiry::class, 'vessel_listing_id');
    }

    public function scopeOpen($query)
    {
        return $query->where('status', 'open')->orderByDesc('created_at');
    }

    public function toPublicArray(): array
    {
        $data = $this->only([
            'id',
            'public_ref_code',
            'vessel_type',
            'year_built',
            'flag_registry',
            'gross_tonnage',
            'deadweight',
            'loa_length',
            'beam',
            'draft',
            'engine_power',
            'trading_area',
            'marketing_description',
            'marketing_description_en',
            'status',
        ]);

        $data['images'] = $this->images;

        return $data;
    }

    public function hasSensitiveData(): bool
    {
        foreach ($this->sensitiveFields as $field) {
            if (! empty($this->$field)) {
                return true;
            }
        }

        return false;
    }
}
