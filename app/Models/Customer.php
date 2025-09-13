<?php

namespace App\Models;

use App\Enums\CustomerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'code','type','name','email','phone','nik','npwp',
        'pic_name','pic_phone','pic_email',
        'city_id','address','postal_code',
    ];

    protected $casts = [
        'type' => CustomerType::class,
    ];

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function isCompany(): bool
    {
        return $this->type === CustomerType::Company;
    }

    public function getTaxIdAttribute(): ?string
    {
        return $this->isCompany() ? $this->npwp : $this->nik;
    }

    public function getTaxLabelAttribute(): string
    {
        return $this->isCompany() ? 'NPWP' : 'NIK';
    }
}
