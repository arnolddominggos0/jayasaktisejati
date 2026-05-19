<?php

declare(strict_types=1);

namespace App\Services\Operational;

use App\Enums\SlaStatus;
use App\Enums\VesselCheckLogStatus;
use App\Enums\VoyageOperationalStatus;
use App\Models\Voyage;
use App\Models\VoyageCheckpoint;
use App\Models\VoyageMilestone;

/**
 * Canonical operational state engine for a single Voyage.
 *
 * Computes severity, readiness, KPI, issues, and available actions
 * in one pass.  Blade / Resource / Widget code should delegate here
 * instead of duplicating business rules.
 */
final class VoyageOperationalState
{
    public Voyage $voyage;

    // ── Core status ──────────────────────────────────────────────────
    public VoyageOperationalStatus $status;
    public string $severity = 'normal';

    // ── Counts ───────────────────────────────────────────────────────
    public int $milestoneOverdueCount = 0;
    public int $milestoneDueTodayCount = 0;
    public int $milestoneCompletedCount = 0;
    public int $milestoneTotalCount = 0;

    // ── Flags ────────────────────────────────────────────────────────
    public bool $hasEtaOverdue = false;
    public bool $hasSailingRisk = false;
    public bool $hasReadinessIssue = false;
    public bool $hasCheckpointOverdue = false;
    public bool $hasPotentialDelay = false;
    public bool $isDelayed = false;
    public bool $hasMilestoneOverdue = false;

    // ── Issues (human-readable) ─────────────────────────────────────
    public array $criticalIssues = [];
    public array $warningIssues = [];

    // ── KPI ──────────────────────────────────────────────────────────
    public ?SlaStatus $otb = null;
    public ?SlaStatus $otd = null;
    public ?SlaStatus $ota = null;
    public ?SlaStatus $sla = null;

    // ── Action availability ─────────────────────────────────────────
    public bool $canInputAtd = false;
    public bool $canInputAta = false;
    public bool $canMonitorAta = false;
    public bool $canAcknowledge = false;
    public bool $canShowMilestone = false;

    // ── Derived values ──────────────────────────────────────────────
    public ?int $sailingDays = null;
    public ?int $daysUntilEtd = null;
    public string $nextActionLabel = 'Monitoring';

    public function __construct(Voyage $voyage)
    {
        $this->voyage = $voyage;
        $this->status = $voyage->operational_status_enum ?? VoyageOperationalStatus::SCHEDULED;

        $this->evaluateMilestones();
        $this->evaluateFlags();
        $this->evaluateIssues();
        $this->evaluateSeverity();
        $this->evaluateKpi();
        $this->evaluateActions();
    }

    // ═════════════════════════════════════════════════════════════════
    // Factory
    // ═════════════════════════════════════════════════════════════════

    public static function for(Voyage $voyage): self
    {
        return new self($voyage);
    }

    // ═════════════════════════════════════════════════════════════════
    // Evaluation steps
    // ═════════════════════════════════════════════════════════════════

    private function evaluateMilestones(): void
    {
        $milestones = $this->voyage->milestones ?? collect();
        $this->milestoneTotalCount = $milestones->count();
        $this->milestoneCompletedCount = $milestones->whereNotNull('actual_date')->count();
        $this->milestoneOverdueCount = $milestones->where('is_overdue', true)->count();
        $this->milestoneDueTodayCount = $milestones->where('is_due_today', true)->count();
        $this->hasMilestoneOverdue = $this->milestoneOverdueCount > 0;
    }

    private function evaluateFlags(): void
    {
        $this->hasEtaOverdue = $this->voyage->eta_overdue ?? false;
        $this->hasSailingRisk = $this->voyage->sailing_risk ?? false;
        $this->isDelayed = $this->voyage->is_delayed ?? false;

        $this->hasCheckpointOverdue = collect($this->voyage->checkpoints ?? [])
            ->contains(fn(VoyageCheckpoint $cp) => !$cp->is_completed && $cp->scheduled_at?->isPast());

        $this->hasPotentialDelay = collect($this->voyage->vesselChecks ?? [])
            ->contains(fn($vc) => $vc->status?->value === VesselCheckLogStatus::POTENTIAL_DELAY->value);

        $this->hasReadinessIssue = $this->hasCheckpointOverdue || $this->hasPotentialDelay;
    }

    private function evaluateIssues(): void
    {
        if ($this->status === VoyageOperationalStatus::DELAYED && $this->voyage->overdue_days > 0) {
            $this->criticalIssues[] = 'Terlambat ' . $this->voyage->overdue_days . ' hari';
        }

        if ($this->hasEtaOverdue) {
            $this->criticalIssues[] = 'ETA Lewat';
        }

        if ($this->hasSailingRisk) {
            $this->warningIssues[] = 'Risiko ETA';
        }

        if ($this->hasPotentialDelay) {
            $this->warningIssues[] = 'Risiko H-1';
        }

        if ($this->hasMilestoneOverdue) {
            $this->warningIssues[] = 'Milestone lewat';
        }

        if ($this->hasCheckpointOverdue) {
            $this->warningIssues[] = 'Checkpoint lewat';
        }
    }

    private function evaluateSeverity(): void
    {
        if (
            ($this->status === VoyageOperationalStatus::DELAYED && $this->voyage->overdue_days > 0)
            || $this->hasEtaOverdue
        ) {
            $this->severity = 'critical';
            return;
        }

        if (
            $this->hasSailingRisk
            || $this->hasMilestoneOverdue
            || $this->hasCheckpointOverdue
            || $this->hasPotentialDelay
        ) {
            $this->severity = 'warning';
            return;
        }

        $this->severity = 'normal';
    }

    private function evaluateKpi(): void
    {
        $this->otb = $this->voyage->otb_status;
        $this->otd = $this->voyage->otd_status;
        $this->ota = $this->voyage->ota_status;
        $this->sla = $this->voyage->sla_status;
    }

    private function evaluateActions(): void
    {
        $v = $this->voyage;

        // Input ATD: no ATD yet
        $this->canInputAtd = is_null($v->atd_at);

        // Input ATA: has ATD but no ATA
        $this->canInputAta = !is_null($v->atd_at) && is_null($v->ata_at);

        // Monitor ATA: currently sailing without ATA
        $this->canMonitorAta = $this->status === VoyageOperationalStatus::SAILING && is_null($v->ata_at);

        // Acknowledge: has issues and not normal
        $this->canAcknowledge = $this->severity !== 'normal';

        // Milestone: has milestones
        $this->canShowMilestone = $this->milestoneTotalCount > 0;

        // Next action label
        $this->nextActionLabel = match (true) {
            $this->canInputAtd => 'Input ATD',
            $this->canMonitorAta => 'Monitor ATA',
            $this->hasPotentialDelay => 'Review Risiko',
            $this->hasEtaOverdue => 'Investigasi Terlambat',
            default => 'Monitoring',
        };

        // Sailing days
        if ($v->atd_at) {
            $this->sailingDays = max(1, (int) $v->atd_at->diffInDays(now()));
        }

        // Days until ETD
        if ($this->status === VoyageOperationalStatus::SCHEDULED && $v->etd) {
            $this->daysUntilEtd = max(0, (int) now()->diffInDays($v->etd, false));
        }
    }

    // ═════════════════════════════════════════════════════════════════
    // Convenience
    // ═════════════════════════════════════════════════════════════════

    public function kpiOk(string $kpi): bool
    {
        return match ($kpi) {
            'otb' => $this->otb?->value === 'ontime',
            'otd' => $this->otd?->value === 'ontime',
            'ota' => $this->ota?->value === 'ontime',
            default => false,
        };
    }

    public function kpiBadge(string $kpi): string
    {
        return $this->kpiOk($kpi) ? 'OK' : 'NG';
    }

    /**
     * Sorting weight for dashboard priority (higher = more important).
     */
    public function priorityWeight(): int
    {
        return match (true) {
            $this->status === VoyageOperationalStatus::DELAYED => 100,
            $this->hasEtaOverdue => 90,
            $this->hasSailingRisk => 80,
            $this->status === VoyageOperationalStatus::SAILING => 70,
            $this->hasCheckpointOverdue => 60,
            $this->hasPotentialDelay => 50,
            $this->status === VoyageOperationalStatus::COMPLETED => 30,
            default => 20,
        };
    }

    /**
     * Calendar chip severity for calendar view.
     */
    public function calendarSeverity(): ?string
    {
        return $this->voyage->departure_delay_severity;
    }

    /**
     * Human-readable delay label for calendar / compact views.
     */
    public function delayLabel(): ?string
    {
        return $this->voyage->delay_label;
    }
}
