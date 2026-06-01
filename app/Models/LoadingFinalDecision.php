<?php

namespace App\Models;

use App\Enums\FinalDecisionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingFinalDecision extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_session_id',
        'status',
        'category',
        'reason',
        'notes',
        'critical_issues',
        'warning_issues',
        // Conditions
        'pillar_issues',
        'drop_floor_issues',
        'pulley_issues',
        'apd_incomplete',
        'mp_unhealthy',
        'equipment_unsafe',
        'unit_unsafe',
        'stock_apd_insufficient',
        'mp_insufficient',
        // Approval
        'requested_by',
        'requested_at',
        'approved_by',
        'approved_at',
        'approval_notes',
        // Corrective action
        'corrective_action',
        'corrective_action_completed_at',
    ];

    protected $casts = [
        'status' => FinalDecisionStatus::class,
        'critical_issues' => 'array',
        'warning_issues' => 'array',
        'pillar_issues' => 'boolean',
        'drop_floor_issues' => 'boolean',
        'pulley_issues' => 'boolean',
        'apd_incomplete' => 'boolean',
        'mp_unhealthy' => 'boolean',
        'equipment_unsafe' => 'boolean',
        'unit_unsafe' => 'boolean',
        'stock_apd_insufficient' => 'boolean',
        'mp_insufficient' => 'boolean',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'corrective_action_completed_at' => 'datetime',
    ];

    // Relationships
    public function loadingSession(): BelongsTo
    {
        return $this->belongsTo(LoadingSession::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', FinalDecisionStatus::Pending);
    }

    public function scopeApproved($query)
    {
        return $query->whereIn('status', [FinalDecisionStatus::Approved, FinalDecisionStatus::Go]);
    }

    public function scopeRejected($query)
    {
        return $query->whereIn('status', [FinalDecisionStatus::Rejected, FinalDecisionStatus::Stop]);
    }

    public function scopeNeedsApproval($query)
    {
        return $query->whereIn('status', [FinalDecisionStatus::Warning, FinalDecisionStatus::Pending]);
    }

    // Business Logic
    public function canProceed(): bool
    {
        return $this->status->canProceed();
    }

    public function isCritical(): bool
    {
        return $this->status->isCritical();
    }

    public function approve(User $approver, ?string $notes = null): void
    {
        $this->approved_by = $approver->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->status = FinalDecisionStatus::Approved;
        $this->save();

        // Update session
        $this->loadingSession->final_decision_status = FinalDecisionStatus::Approved;
        $this->loadingSession->final_decision_by = $approver->id;
        $this->loadingSession->final_decision_at = now();
        $this->loadingSession->final_decision_notes = $notes;
        $this->loadingSession->save();
    }

    public function reject(User $rejector, ?string $notes = null): void
    {
        $this->approved_by = $rejector->id;
        $this->approved_at = now();
        $this->approval_notes = $notes;
        $this->status = FinalDecisionStatus::Rejected;
        $this->save();

        // Update session
        $this->loadingSession->final_decision_status = FinalDecisionStatus::Rejected;
        $this->loadingSession->final_decision_by = $rejector->id;
        $this->loadingSession->final_decision_at = now();
        $this->loadingSession->final_decision_notes = $notes;
        $this->loadingSession->status = \App\Enums\LoadingStatus::Rejected;
        $this->loadingSession->save();
    }

    public function getIssuesSummary(): array
    {
        $issues = [];

        if ($this->pillar_issues) {
            $issues[] = 'Pilar rack bermasalah';
        }
        if ($this->drop_floor_issues) {
            $issues[] = 'Drop floor tidak aman';
        }
        if ($this->pulley_issues) {
            $issues[] = 'Katrol/alat tidak aman';
        }
        if ($this->apd_incomplete) {
            $issues[] = 'APD tidak lengkap';
        }
        if ($this->mp_unhealthy) {
            $issues[] = 'Ada MP yang tidak sehat';
        }
        if ($this->equipment_unsafe) {
            $issues[] = 'Peralatan tidak aman';
        }
        if ($this->unit_unsafe) {
            $issues[] = 'Unit tidak aman';
        }
        if ($this->stock_apd_insufficient) {
            $issues[] = 'Stok APD tidak mencukupi';
        }
        if ($this->mp_insufficient) {
            $issues[] = 'MP tidak mencukupi';
        }

        return $issues;
    }

    public static function createFromSession(LoadingSession $session): self
    {
        $decisionStatus = $session->evaluateFinalDecision();

        // Collect all issues
        $criticalIssues = [];
        $warningIssues = [];

        if ($session->rackContainerCheck) {
            $criticalIssues = array_merge($criticalIssues, $session->rackContainerCheck->getFindings());
        }
        if ($session->equipmentCheck) {
            $criticalIssues = array_merge($criticalIssues, $session->equipmentCheck->getFindings());
        }
        if ($session->unitCheck) {
            $criticalIssues = array_merge($criticalIssues, $session->unitCheck->getFindings());
        }

        // Add session-level issues
        if ($session->mp_unfit_count > 0) {
            $criticalIssues[] = [
                'category' => 'health',
                'severity' => 'critical',
                'item' => 'Kesehatan MP',
                'issue' => "{$session->mp_unfit_count} MP tidak fit",
            ];
        }
        if (! $session->apd_complete) {
            $criticalIssues[] = [
                'category' => 'apd',
                'severity' => 'critical',
                'item' => 'APD',
                'issue' => 'APD tidak lengkap',
            ];
        }
        if (! $session->mp_sufficient) {
            $criticalIssues[] = [
                'category' => 'manpower',
                'severity' => 'critical',
                'item' => 'Ketersediaan MP',
                'issue' => 'MP tidak mencukupi',
            ];
        }
        if (! $session->stock_apd_sufficient) {
            $criticalIssues[] = [
                'category' => 'stock',
                'severity' => 'critical',
                'item' => 'Stok APD',
                'issue' => 'Stok APD tidak mencukupi',
            ];
        }

        $decision = self::create([
            'loading_session_id' => $session->id,
            'status' => $decisionStatus,
            'category' => 'automatic',
            'critical_issues' => $criticalIssues,
            'warning_issues' => $warningIssues,
            'pillar_issues' => ! $session->rack_pillars_ok,
            'drop_floor_issues' => ! $session->drop_floor_ok,
            'pulley_issues' => ! $session->equipment_safe,
            'apd_incomplete' => ! $session->apd_complete,
            'mp_unhealthy' => $session->mp_unfit_count > 0,
            'equipment_unsafe' => ! $session->equipment_safe,
            'unit_unsafe' => ! $session->unit_measurements_ok,
            'stock_apd_insufficient' => ! $session->stock_apd_sufficient,
            'mp_insufficient' => ! $session->mp_sufficient,
            'requested_by' => $session->coordinator_user_id,
            'requested_at' => now(),
        ]);

        // Update session
        $session->final_decision_status = $decisionStatus;
        $session->final_decision_completed = true;
        $session->save();

        return $decision;
    }
}
