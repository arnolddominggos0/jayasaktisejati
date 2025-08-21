<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    protected $fillable = ['code', 'name'];

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
