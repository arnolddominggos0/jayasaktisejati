<?php

namespace App\Models;

use App\Enums\FinalDecisionStatus;
use App\Enums\LoadingOperationType;
use App\Enums\LoadingStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class LoadingSession extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'briefing_session_id',
        'shipment_id',
        'depot_id',
        'coordinator_user_id',
        'branch_id',
        'operation_type',
        'status',
        'current_step',
        'started_at',
        'completed_at',
        'stopped_at',
        'mp_attendance_completed',
        'health_check_completed',
        'apd_check_completed',
        'equipment_check_completed',
        'rack_container_check_completed',
        'unit_check_completed',
        'stock_apd_check_completed',
        'manpower_availability_completed',
        'final_decision_completed',
        'mp_required',
        'mp_present',
        'mp_absent',
        'mp_sick',
        'mp_sufficient',
        'mp_fit_count',
        'mp_unfit_count',
        'apd_complete',
        'apd_clean',
        'equipment_safe',
        'rack_container_safe',
        'rack_pillars_ok',
        'drop_floor_ok',
        'container_structure_ok',
        'unit_measurements_ok',
        'stock_apd_sufficient',
        'final_decision_status',
        'final_decision_notes',
        'final_decision_by',
        'final_decision_at',
        'gps_latitude',
        'gps_longitude',
        'location_address',
        'critical_issues_count',
        'warning_issues_count',
        'general_notes',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'stopped_at' => 'datetime',
        'final_decision_at' => 'datetime',
        'mp_attendance_completed' => 'boolean',
        'health_check_completed' => 'boolean',
        'apd_check_completed' => 'boolean',
        'equipment_check_completed' => 'boolean',
        'rack_container_check_completed' => 'boolean',
        'unit_check_completed' => 'boolean',
        'stock_apd_check_completed' => 'boolean',
        'manpower_availability_completed' => 'boolean',
        'final_decision_completed' => 'boolean',
        'mp_sufficient' => 'boolean',
        'apd_complete' => 'boolean',
        'apd_clean' => 'boolean',
        'equipment_safe' => 'boolean',
        'rack_container_safe' => 'boolean',
        'rack_pillars_ok' => 'boolean',
        'drop_floor_ok' => 'boolean',
        'container_structure_ok' => 'boolean',
        'unit_measurements_ok' => 'boolean',
        'stock_apd_sufficient' => 'boolean',
        'status' => LoadingStatus::class,
        'operation_type' => LoadingOperationType::class,
        'final_decision_status' => FinalDecisionStatus::class,
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->code)) {
                $model->code = self::generateCode();
            }
        });
    }

    public static function generateCode(): string
    {
        $prefix = 'LD-' . date('Y') . '-';
        $lastCode = self::where('code', 'like', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastCode) {
            $lastNumber = (int) Str::afterLast($lastCode->code, '-');
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    // Relationships
    public function briefingSession(): BelongsTo
    {
        return $this->belongsTo(BriefingSession::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function depot(): BelongsTo
    {
        return $this->belongsTo(Depot::class);
    }

    public function coordinator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'coordinator_user_id');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function finalDecisionBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'final_decision_by');
    }

    public function rackContainerCheck(): HasOne
    {
        return $this->hasOne(RackContainerCheck::class);
    }

    public function equipmentCheck(): HasOne
    {
        return $this->hasOne(EquipmentCheck::class);
    }

    public function unitCheck(): HasOne
    {
        return $this->hasOne(UnitCheck::class);
    }

    public function finalDecision(): HasOne
    {
        return $this->hasOne(LoadingFinalDecision::class);
    }

    public function findings(): HasMany
    {
        return $this->hasMany(LoadingFinding::class);
    }

    public function photos(): HasMany
    {
        return $this->hasMany(LoadingPhoto::class);
    }

    // Scopes
    public function scopeDraft($query)
    {
        return $query->where('status', LoadingStatus::Draft);
    }

    public function scopeInProgress($query)
    {
        return $query->whereIn('status', [
            LoadingStatus::InProgress,
            LoadingStatus::MpAttendanceCheck,
            LoadingStatus::HealthCheck,
            LoadingStatus::ApdCheck,
            LoadingStatus::EquipmentCheck,
            LoadingStatus::RackContainerCheck,
            LoadingStatus::UnitCheck,
            LoadingStatus::StockApdCheck,
            LoadingStatus::ManpowerAvailabilityCheck,
            LoadingStatus::FinalDecision,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', LoadingStatus::Completed);
    }

    public function scopeStopped($query)
    {
        return $query->where('status', LoadingStatus::Stopped);
    }

    public function scopeHasCriticalIssues($query)
    {
        return $query->where('critical_issues_count', '>', 0);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    public function scopeByDepot($query, $depotId)
    {
        return $query->where('depot_id', $depotId);
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    // Business Logic
    public function canProceed(): bool
    {
        if ($this->final_decision_status === null) {
            return false;
        }

        return $this->final_decision_status->canProceed();
    }

    public function isStopped(): bool
    {
        return $this->status === LoadingStatus::Stopped ||
               $this->status === LoadingStatus::Rejected;
    }

    public function isCompleted(): bool
    {
        return $this->status === LoadingStatus::Completed;
    }

    public function getProgressPercentage(): int
    {
        $steps = [
            'mp_attendance_completed',
            'health_check_completed',
            'apd_check_completed',
            'equipment_check_completed',
            'rack_container_check_completed',
            'unit_check_completed',
            'stock_apd_check_completed',
            'manpower_availability_completed',
            'final_decision_completed',
        ];

        $completed = collect($steps)->filter(fn ($step) => $this->$step)->count();

        return (int) round(($completed / count($steps)) * 100);
    }

    public function getNextStep(): ?string
    {
        $steps = [
            'mp_attendance_completed' => 'mp_attendance_check',
            'health_check_completed' => 'health_check',
            'apd_check_completed' => 'apd_check',
            'equipment_check_completed' => 'equipment_check',
            'rack_container_check_completed' => 'rack_container_check',
            'unit_check_completed' => 'unit_check',
            'stock_apd_check_completed' => 'stock_apd_check',
            'manpower_availability_completed' => 'manpower_availability_check',
            'final_decision_completed' => 'final_decision',
        ];

        foreach ($steps as $field => $step) {
            if (! $this->$field) {
                return $step;
            }
        }

        return null;
    }

    public function getStatusSummary(): array
    {
        return [
            'mp_attendance' => $this->mp_attendance_completed,
            'health_check' => $this->health_check_completed,
            'apd_check' => $this->apd_check_completed,
            'equipment_check' => $this->equipment_check_completed,
            'rack_container_check' => $this->rack_container_check_completed,
            'unit_check' => $this->unit_check_completed,
            'stock_apd_check' => $this->stock_apd_check_completed,
            'manpower_availability' => $this->manpower_availability_completed,
            'final_decision' => $this->final_decision_completed,
        ];
    }

    public function recalculateSafetyStatus(): void
    {
        // Rack Container Safety
        if ($this->rackContainerCheck) {
            $this->rack_pillars_ok = $this->rackContainerCheck->areAllPillarsSafe();
            $this->drop_floor_ok = $this->rackContainerCheck->areAllDropFloorsSafe();
            $this->container_structure_ok = $this->rackContainerCheck->isContainerStructureSafe();
            $this->rack_container_safe = $this->rackContainerCheck->isOverallSafe();
        }

        // Equipment Safety
        if ($this->equipmentCheck) {
            $this->equipment_safe = $this->equipmentCheck->isOverallSafe();
        }

        // Unit Safety
        if ($this->unitCheck) {
            $this->unit_measurements_ok = $this->unitCheck->unit_safe_for_loading;
        }

        $this->save();
    }

    public function evaluateFinalDecision(): FinalDecisionStatus
    {
        $criticalIssues = [];

        // Check Rack Pillar
        if (! $this->rack_pillars_ok) {
            $criticalIssues[] = 'Pilar rack rusak atau tidak aman';
        }

        // Check Drop Floor
        if (! $this->drop_floor_ok) {
            $criticalIssues[] = 'Drop floor tidak kuat atau rusak';
        }

        // Check Pulley/Katrol
        if (! $this->equipment_safe) {
            $criticalIssues[] = 'Alat/katrol tidak aman';
        }

        // Check APD
        if (! $this->apd_complete) {
            $criticalIssues[] = 'APD tidak lengkap';
        }

        // Check MP Health
        if ($this->mp_unfit_count > 0) {
            $criticalIssues[] = 'Ada MP yang tidak sehat';
        }

        // Check MP Sufficient
        if (! $this->mp_sufficient) {
            $criticalIssues[] = 'MP tidak mencukupi';
        }

        // Check Stock APD
        if (! $this->stock_apd_sufficient) {
            $criticalIssues[] = 'Stok APD tidak mencukupi';
        }

        // Check Container Structure
        if (! $this->container_structure_ok) {
            $criticalIssues[] = 'Struktur container rusak/bocor';
        }

        // Check Unit Safety
        if (! $this->unit_measurements_ok) {
            $criticalIssues[] = 'Unit tidak aman untuk loading';
        }

        if (count($criticalIssues) > 0) {
            return FinalDecisionStatus::Stop;
        }

        // Check for warnings
        $warnings = [];
        if ($this->warning_issues_count > 0) {
            $warnings[] = 'Ada temuan dengan status warning';
        }

        if (count($warnings) > 0) {
            return FinalDecisionStatus::Warning;
        }

        return FinalDecisionStatus::Go;
    }
}
