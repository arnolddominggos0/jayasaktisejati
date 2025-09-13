<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Enums\ManpowerRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Manpower extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'role',
        'phone',
        'license_number',
        'branch_id'
    ];

    protected $casts = [
        'role' => ManpowerRole::class,
    ];

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function armadas(): BelongsToMany
    {
        return $this->belongsToMany(Armada::class)->withTimestamps();
    }
}
