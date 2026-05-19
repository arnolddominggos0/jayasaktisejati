<?php

declare(strict_types=1);

namespace App\Services\Operational;

use App\Enums\VoyageOperationalStatus;
use App\Models\Voyage;
use Illuminate\Support\Collection;

/**
 * Lightweight snapshot of voyage operational state for views.
 *
 * Pre-computes everything so Blade files only read, never branch on
 * business rules.
 */
final class VoyageOperationalSnapshot
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

    /**
     * Wrap a collection of voyages into snapshots.
     *
     * @param Collection<int, Voyage> $voyages
     * @return Collection<int, self>
     */
    public static function collection(Collection $voyages): Collection
    {
        return $voyages->map(fn(Voyage $v) => self::for($v));
    }

    /**
     * Sort snapshots by operational priority (descending).
     *
     * @param Collection<int, self> $snapshots
     * @return Collection<int, self>
     */
    public static function sortByPriority(Collection $snapshots): Collection
    {
        return $snapshots
            ->sortByDesc(fn(self $s) => $s->state->priorityWeight())
            ->values();
    }

    // ── Passthrough helpers for ergonomic Blade access ───────────────

    public function severity(): string
    {
        return $this->state->severity;
    }

    public function status(): VoyageOperationalStatus
    {
        return $this->state->status;
    }

    public function hasCriticalIssues(): bool
    {
        return count($this->state->criticalIssues) > 0;
    }

    public function hasWarningIssues(): bool
    {
        return count($this->state->warningIssues) > 0;
    }

    public function hasAnyIssue(): bool
    {
        return $this->hasCriticalIssues() || $this->hasWarningIssues();
    }

    public function isAcknowledgable(): bool
    {
        return $this->state->canAcknowledge;
    }
}
