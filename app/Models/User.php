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
        'scope_branch_id',
        'scope_unit_id',
        'scope_unit_type',
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

    public function scopeBranch()
    {
        return $this->belongsTo(Branch::class, 'scope_branch_id');
    }

    public function scopeUnit(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return match ($this->scope_unit_type) {
            'depot' => $this->belongsTo(Depot::class, 'scope_unit_id'),
            'pool' => $this->belongsTo(Pool::class, 'scope_unit_id'),
            default => $this->belongsTo(Depot::class, 'scope_unit_id'),
        };
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    protected static function booted(): void
    {
        static::saving(function (User $user) {
            // Guard: if any canonical scope field changes, validate consistency.
            // scope_unit_type/scope_unit_id must point to a unit that lists this user
            // as coordinator, and scope_branch_id must match that unit's branch.
            $scopeDirty = $user->isDirty(['scope_branch_id', 'scope_unit_type', 'scope_unit_id']);

            if ($scopeDirty) {
                $type = $user->scope_unit_type;
                $unitId = $user->scope_unit_id;

                if ($type && $unitId) {
                    $model = $type === 'depot' ? Depot::class : Pool::class;
                    $unit = $model::find($unitId);

                    if (! $unit) {
                        throw new \InvalidArgumentException("Unit {$type} with ID {$unitId} does not exist.");
                    }

                    if ($unit->coordinator_user_id !== $user->id) {
                        throw new \InvalidArgumentException('User scope_unit_id does not match the unit\'s coordinator assignment.');
                    }

                    if ($unit->branch_id !== $user->scope_branch_id) {
                        throw new \InvalidArgumentException('User scope_branch_id does not match the unit\'s branch.');
                    }
                }
            }
        });
    }

    // ── Role taxonomy ─────────────────────────────────────────────────────────
    //
    // super_admin     → God Mode. Full system access. Manages users, roles,
    //                   system config, global audit. Not a daily business user.
    //
    // office_admin    → Business Operator. Manages shipments, customers, voyage
    //                   planning, and monitoring within their assigned branch.
    //                   Branch-scoped. Cannot access system admin functions.
    //
    // field_coordinator → Field Operator. Executes physical logistics: pickup,
    //                   handover, stuffing, loading, delivery. Depot-scoped.
    //                   Owns the operational lifecycle of each shipment.
    //
    // customer        → Customer Portal only. Read-only access to their own
    //                   shipments via /portal. Requires customer_id link.
    //
    // ─────────────────────────────────────────────────────────────────────────

    public function isSuperAdmin(): bool
    {
        return $this->hasRole('super_admin');
    }

    public function isOfficeAdmin(): bool
    {
        return $this->hasRole('office_admin');
    }

    /**
     * True for any user who belongs to the "office" side of the business:
     * super_admin (global) or office_admin (branch-scoped).
     * Use this when a check means "can operate the admin panel".
     */
    public function isOfficeUser(): bool
    {
        return $this->hasAnyRole(['super_admin', 'office_admin']);
    }

    public function isFieldCoordinator(): bool
    {
        return $this->hasRole('field_coordinator');
    }

    public function isCustomer(): bool
    {
        return $this->hasRole('customer');
    }

    public function isCmsEditor(): bool
    {
        return $this->hasRole('cms');
    }

    // ── Panel access ──────────────────────────────────────────────────────────

    public function canAccessPanel(Panel $panel): bool
    {
        return match ($panel->getId()) {
            // Admin panel: office operators + system admin
            'admin'    => $this->isOfficeUser(),
            // FC panel: field coordinators + super_admin for oversight/debug
            'fc'       => $this->isFieldCoordinator() || $this->isSuperAdmin(),
            // Customer portal: customers with a linked customer record
            'customer' => $this->isCustomer() && $this->customer_id !== null,
            // CMS panel: CMS editors + super_admin
            'cms'      => $this->isCmsEditor() || $this->isSuperAdmin(),
            default    => false,
        };
    }

    public function getDefaultGuardName(): string
    {
        return 'web';
    }

    /**
     * Canonical branch identifier: prefers scope_branch_id, falls back to legacy branch_id.
     */
    public function effectiveBranchId(): ?int
    {
        return $this->scope_branch_id ?? $this->branch_id;
    }

    public function canUpdateVesselDepart(Shipment $shipment): bool
    {
        if (! $this->isFieldCoordinator()) {
            return false;
        }

        if (!$shipment->assigned_depot_id) {
            return false;
        }

        // Canonical scope check first; fallback to legacy depot lookup.
        if ($this->scope_unit_type === 'depot' && $this->scope_unit_id === $shipment->assigned_depot_id) {
            return true;
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
