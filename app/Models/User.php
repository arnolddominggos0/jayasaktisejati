<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    protected $fillable = [
        'name',
        'email',
        'password',
        'branch_id',
        'customer_id',
        'port_id',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            'admin' => $this->hasAnyRole(['super_admin', 'office_admin']),
            'fc'    => $this->hasAnyRole(['field_coordinator']),
            'customer' => $this->hasRole('customer') && $this->customer_id !== null,
            default => false,
        };
    }

    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    public function canUpdateVesselDepart(Shipment $shipment): bool
    {
        if (!$this->hasRole('field_coordinator')) {
            return false;
        }

        if (!$shipment->assigned_depot_id) {
            return false;
        }

        return \App\Models\Depot::where('id', $shipment->assigned_depot_id)
            ->where('coordinator_user_id', $this->id)
            ->exists();
    }

    public function port()
    {
        return $this->belongsTo(Port::class, 'port_id');
    }
}
