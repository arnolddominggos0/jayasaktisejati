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

    // ═════════════════════════════════════════════════════════════════
    // Collection-level categorization
    // Single source of truth for voyage categorization, so the rules are not
    // duplicated as inline filter chains across Blade templates.

    /**
     * Categorize a collection of voyages into operational groups.
     *
     * Returns an array with keys:
     *   'delayed'          - delayed voyages
     *   'sailing_eta_risk' - sailing voyages with ETA risk or overdue
     *   'sailing_normal'   - sailing voyages without ETA risk
     *   'readiness_issue'  - scheduled voyages with readiness issues
     *   'scheduled_normal'  - scheduled voyages without issues
     *   'completed'        - completed voyages
     *
     * @param Collection<int, Voyage> $voyages
     * @return array<string, Collection<int, Voyage>>
     */
    public static function categorize(Collection $voyages): array
    {
        $snapshots = self::collection($voyages)->keyBy(fn($s) => $s->voyage->id);

        $delayed = collect();
        $sailingEtaRisk = collect();
        $sailingNormal = collect();
        $readinessIssue = collect();
        $scheduledNormal = collect();
        $completed = collect();

        foreach ($voyages as $voyage) {
            $snap = $snapshots[$voyage->id] ?? null;
            $state = $snap?->state ?? VoyageOperationalState::for($voyage);

            if ($state->isDelayed()) {
                $delayed->push($voyage);
            } elseif ($state->isSailing()) {
                if ($state->hasEtaOverdue || $state->hasSailingRisk) {
                    $sailingEtaRisk->push($voyage);
                } else {
                    $sailingNormal->push($voyage);
                }
            } elseif ($state->isScheduled()) {
                if ($state->hasReadinessIssue) {
                    $readinessIssue->push($voyage);
                } else {
                    $scheduledNormal->push($voyage);
                }
            } elseif ($state->isCompleted()) {
                $completed->push($voyage);
            }
        }

        return compact(
            'delayed',
            'sailingEtaRisk',
            'sailingNormal',
            'readinessIssue',
            'scheduledNormal',
            'completed'
        );
    }

    /**
     * Compute KPI summary from a collection of voyages.
     *
     * Replaces the inline filter/count logic in monitoring-kapal-tam.blade.php.
     *
     * @param Collection<int, Voyage> $voyages
     * @return array{total:int, otd_ok:int, ota_ok:int, overdue_count:int, delayed_count:int, sailing_count:int, completed_count:int, scheduled_count:int}
     */
    public static function kpiSummary(Collection $voyages): array
    {
        $total = $voyages->count();
        $delayedCount = 0;
        $sailingCount = 0;
        $completedCount = 0;
        $scheduledCount = 0;
        $otdOk = 0;
        $otdTotal = 0;  // voyages with ATD recorded
        $otaOk = 0;
        $otaTotal = 0;  // voyages with ATA recorded
        $overdueCount = 0;

        foreach ($voyages as $voyage) {
            $state = VoyageOperationalState::for($voyage);

            if ($state->isDelayed()) { $delayedCount++; }
            elseif ($state->isSailing()) { $sailingCount++; }
            elseif ($state->isCompleted()) { $completedCount++; }
            elseif ($state->isScheduled()) { $scheduledCount++; }

            if ($state->otd !== null) {
                $otdTotal++;
                if ($state->kpiOk('otd')) { $otdOk++; }
            }

            if ($state->ota !== null) {
                $otaTotal++;
                if ($state->kpiOk('ota')) { $otaOk++; }
            }

            $overdueCount += $state->milestoneOverdueCount;
        }

        return [
            'total'             => $total,
            'delayed_count'     => $delayedCount,
            'sailing_count'     => $sailingCount,
            'completed_count'   => $completedCount,
            'scheduled_count'   => $scheduledCount,
            'otd_ok'            => $otdOk,
            'otd_total'         => $otdTotal,
            'ota_ok'            => $otaOk,
            'ota_total'         => $otaTotal,
            'otd_percent'       => $otdTotal > 0 ? (int) round(($otdOk / $otdTotal) * 100) : 0,
            'ota_percent'       => $otaTotal > 0 ? (int) round(($otaOk / $otaTotal) * 100) : 0,
            'overdue_count'     => $overdueCount,
        ];
    }
}
