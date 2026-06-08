<?php

declare(strict_types=1);

namespace App\Services\Operational;

use App\Enums\VesselCheckLogStatus;
use App\Enums\VoyageOperationalStatus;
use App\Models\Voyage;

/**
 * Canonical workflow policy for voyage operational actions.
 *
 * Every action that changes state or requires authorization must be
 * evaluated through this policy.  Blade templates, Filament actions,
 * and notification rules MUST delegate here instead of duplicating
 * conditions.
 *
 * WHY: Previously, button visibility, action validation, escalation
 * permissions, and delay-case creation were scattered across Blade
 * conditionals, Livewire methods, and Filament actions with inline
 * status checks.  Any change to the rules required hunting through
 * every file.  Centralizing here ensures a single source of truth
 * for every workflow transition.
 */
final class VoyageWorkflowPolicy
{
    public function __construct(
        public readonly Voyage $voyage,
        public readonly VoyageOperationalState $state,
    ) {
    }

    public static function for(Voyage $voyage): self
    {
        return new self($voyage, VoyageOperationalState::for($voyage));
    }

    // ── Status transition checks ────────────────────────────────────

    public function canStartSailing(): bool
    {
        return $this->state->status === VoyageOperationalStatus::SCHEDULED
            && $this->state->canInputAtd;
    }

    public function canMarkArrival(): bool
    {
        return $this->state->canInputAta;
    }

    public function canCloseVoyage(): bool
    {
        return $this->voyage->ata_at !== null
            && $this->state->status === VoyageOperationalStatus::COMPLETED
            && ($this->voyage->registry_status?->value ?? '') !== 'closed';
    }

    // ── Delay & escalation checks ──────────────────────────────────

    public function canCreateDelayCase(): bool
    {
        return $this->state->hasPotentialDelay
            || $this->state->isDelayed
            || $this->state->hasEtaOverdue;
    }

    public function canEscalate(): bool
    {
        return ($this->state->isDelayed && $this->voyage->overdue_days > 2)
            || $this->state->hasEtaOverdue;
    }

    public function canResolve(): bool
    {
        return $this->state->isDelayed
            || $this->state->hasPotentialDelay;
    }

    // ── Acknowledge ────────────────────────────────────────────────

    public function canAcknowledge(): bool
    {
        return $this->state->canAcknowledge;
    }

    // ── Readiness checks ───────────────────────────────────────────

    public function canUpdateVesselCheck(): bool
    {
        return $this->state->status === VoyageOperationalStatus::SCHEDULED;
    }

    public function isVesselCheckLate(): bool
    {
        return collect($this->voyage->vesselChecks ?? [])
            ->contains(
                fn($vc) => $vc->status?->value === VesselCheckLogStatus::LATE->value
            );
    }
}