<?php

namespace App\Models;

use App\Enums\ContainerStructureStatus;
use App\Enums\DropFloorCondition;
use App\Enums\DropFloorStrength;
use App\Enums\IronHookStatus;
use App\Enums\RackPillarCondition;
use App\Enums\RackPulleyHookStatus;
use App\Enums\RackTieStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RackContainerCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_session_id',
        // Pillar A - Depan Kanan
        'pillar_a_condition',
        'pillar_a_pulley_hook',
        'pillar_a_tie_status',
        'pillar_a_photo',
        'pillar_a_notes',
        // Pillar B - Depan Kiri
        'pillar_b_condition',
        'pillar_b_pulley_hook',
        'pillar_b_tie_status',
        'pillar_b_photo',
        'pillar_b_notes',
        // Pillar C - Belakang Kanan
        'pillar_c_condition',
        'pillar_c_pulley_hook',
        'pillar_c_tie_status',
        'pillar_c_photo',
        'pillar_c_notes',
        // Pillar D - Belakang Kiri
        'pillar_d_condition',
        'pillar_d_pulley_hook',
        'pillar_d_tie_status',
        'pillar_d_photo',
        'pillar_d_notes',
        // Drop Floor Front
        'drop_floor_front_condition',
        'drop_floor_front_strength',
        'drop_floor_front_iron_hook',
        'drop_floor_front_photo',
        'drop_floor_front_notes',
        // Drop Floor Rear
        'drop_floor_rear_condition',
        'drop_floor_rear_strength',
        'drop_floor_rear_iron_hook',
        'drop_floor_rear_photo',
        'drop_floor_rear_notes',
        // Container Structure
        'container_wall_status',
        'container_floor_status',
        'container_roof_status',
        'container_structure_photo',
        'container_structure_notes',
        // Safety Summary
        'all_pillars_safe',
        'all_drop_floors_safe',
        'container_structure_safe',
        'overall_safe',
        'critical_issues_count',
        'warning_issues_count',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'all_pillars_safe' => 'boolean',
        'all_drop_floors_safe' => 'boolean',
        'container_structure_safe' => 'boolean',
        'overall_safe' => 'boolean',
        'pillar_a_condition' => RackPillarCondition::class,
        'pillar_b_condition' => RackPillarCondition::class,
        'pillar_c_condition' => RackPillarCondition::class,
        'pillar_d_condition' => RackPillarCondition::class,
        'pillar_a_pulley_hook' => RackPulleyHookStatus::class,
        'pillar_b_pulley_hook' => RackPulleyHookStatus::class,
        'pillar_c_pulley_hook' => RackPulleyHookStatus::class,
        'pillar_d_pulley_hook' => RackPulleyHookStatus::class,
        'pillar_a_tie_status' => RackTieStatus::class,
        'pillar_b_tie_status' => RackTieStatus::class,
        'pillar_c_tie_status' => RackTieStatus::class,
        'pillar_d_tie_status' => RackTieStatus::class,
        'drop_floor_front_condition' => DropFloorCondition::class,
        'drop_floor_rear_condition' => DropFloorCondition::class,
        'drop_floor_front_strength' => DropFloorStrength::class,
        'drop_floor_rear_strength' => DropFloorStrength::class,
        'drop_floor_front_iron_hook' => IronHookStatus::class,
        'drop_floor_rear_iron_hook' => IronHookStatus::class,
        'container_wall_status' => ContainerStructureStatus::class,
        'container_floor_status' => ContainerStructureStatus::class,
        'container_roof_status' => ContainerStructureStatus::class,
    ];

    // Relationships
    public function loadingSession(): BelongsTo
    {
        return $this->belongsTo(LoadingSession::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    // Business Logic - Pillar Safety Checks
    public function isPillarASafe(): bool
    {
        return $this->pillar_a_condition !== RackPillarCondition::Damaged &&
               ! $this->pillar_a_pulley_hook?->isCritical() &&
               ! $this->pillar_a_tie_status?->isCritical();
    }

    public function isPillarBSafe(): bool
    {
        return $this->pillar_b_condition !== RackPillarCondition::Damaged &&
               ! $this->pillar_b_pulley_hook?->isCritical() &&
               ! $this->pillar_b_tie_status?->isCritical();
    }

    public function isPillarCSafe(): bool
    {
        return $this->pillar_c_condition !== RackPillarCondition::Damaged &&
               ! $this->pillar_c_pulley_hook?->isCritical() &&
               ! $this->pillar_c_tie_status?->isCritical();
    }

    public function isPillarDSafe(): bool
    {
        return $this->pillar_d_condition !== RackPillarCondition::Damaged &&
               ! $this->pillar_d_pulley_hook?->isCritical() &&
               ! $this->pillar_d_tie_status?->isCritical();
    }

    public function areAllPillarsSafe(): bool
    {
        return $this->isPillarASafe() &&
               $this->isPillarBSafe() &&
               $this->isPillarCSafe() &&
               $this->isPillarDSafe();
    }

    // Drop Floor Safety Checks
    public function isDropFloorFrontSafe(): bool
    {
        return $this->drop_floor_front_condition !== DropFloorCondition::Bent &&
               $this->drop_floor_front_strength !== DropFloorStrength::Weak &&
               ! $this->drop_floor_front_iron_hook?->isCritical();
    }

    public function isDropFloorRearSafe(): bool
    {
        return $this->drop_floor_rear_condition !== DropFloorCondition::Bent &&
               $this->drop_floor_rear_strength !== DropFloorStrength::Weak &&
               ! $this->drop_floor_rear_iron_hook?->isCritical();
    }

    public function areAllDropFloorsSafe(): bool
    {
        return $this->isDropFloorFrontSafe() && $this->isDropFloorRearSafe();
    }

    // Container Structure Safety
    public function isContainerStructureSafe(): bool
    {
        return $this->container_wall_status !== ContainerStructureStatus::Leaking &&
               $this->container_floor_status !== ContainerStructureStatus::Leaking &&
               $this->container_roof_status !== ContainerStructureStatus::Leaking;
    }

    // Overall Safety
    public function isOverallSafe(): bool
    {
        return $this->areAllPillarsSafe() &&
               $this->areAllDropFloorsSafe() &&
               $this->isContainerStructureSafe();
    }

    // Count Issues
    public function countCriticalIssues(): int
    {
        $count = 0;

        // Check pillars
        foreach (['a', 'b', 'c', 'd'] as $pillar) {
            $condition = $this->{"pillar_{$pillar}_condition"};
            $hook = $this->{"pillar_{$pillar}_pulley_hook"};
            $tie = $this->{"pillar_{$pillar}_tie_status"};

            if ($condition === RackPillarCondition::Damaged) $count++;
            if ($hook?->isCritical()) $count++;
            if ($tie?->isCritical()) $count++;
        }

        // Check drop floors
        if ($this->drop_floor_front_condition === DropFloorCondition::Bent) $count++;
        if ($this->drop_floor_front_strength === DropFloorStrength::Weak) $count++;
        if ($this->drop_floor_front_iron_hook?->isCritical()) $count++;

        if ($this->drop_floor_rear_condition === DropFloorCondition::Bent) $count++;
        if ($this->drop_floor_rear_strength === DropFloorStrength::Weak) $count++;
        if ($this->drop_floor_rear_iron_hook?->isCritical()) $count++;

        // Check container structure
        if ($this->container_wall_status === ContainerStructureStatus::Leaking) $count++;
        if ($this->container_floor_status === ContainerStructureStatus::Leaking) $count++;
        if ($this->container_roof_status === ContainerStructureStatus::Leaking) $count++;

        return $count;
    }

    public function countWarningIssues(): int
    {
        $count = 0;

        // Check pillars for warnings
        foreach (['a', 'b', 'c', 'd'] as $pillar) {
            $condition = $this->{"pillar_{$pillar}_condition"};
            if ($condition === RackPillarCondition::NotStraight) $count++;
        }

        // Check container structure for damages (not leaking)
        if ($this->container_wall_status === ContainerStructureStatus::Damaged) $count++;
        if ($this->container_floor_status === ContainerStructureStatus::Damaged) $count++;
        if ($this->container_roof_status === ContainerStructureStatus::Damaged) $count++;

        return $count;
    }

    // Update counts and save
    public function updateIssueCounts(): void
    {
        $this->critical_issues_count = $this->countCriticalIssues();
        $this->warning_issues_count = $this->countWarningIssues();
        $this->all_pillars_safe = $this->areAllPillarsSafe();
        $this->all_drop_floors_safe = $this->areAllDropFloorsSafe();
        $this->container_structure_safe = $this->isContainerStructureSafe();
        $this->overall_safe = $this->isOverallSafe();
        $this->save();

        // Update parent session
        $this->loadingSession?->recalculateSafetyStatus();
    }

    // Get findings for this check
    public function getFindings(): array
    {
        $findings = [];

        // Pillars
        foreach (['a', 'b', 'c', 'd'] as $pillar) {
            $condition = $this->{"pillar_{$pillar}_condition"};
            $hook = $this->{"pillar_{$pillar}_pulley_hook"};
            $tie = $this->{"pillar_{$pillar}_tie_status"};
            $pillarName = match($pillar) {
                'a' => 'Pilar Depan Kanan (A)',
                'b' => 'Pilar Depan Kiri (B)',
                'c' => 'Pilar Belakang Kanan (C)',
                'd' => 'Pilar Belakang Kiri (D)',
            };

            if ($condition?->isCritical()) {
                $findings[] = [
                    'category' => 'rack_pillar',
                    'severity' => 'critical',
                    'item' => $pillarName,
                    'issue' => "Kondisi: {$condition->label()}",
                ];
            }
            if ($hook?->isCritical()) {
                $findings[] = [
                    'category' => 'rack_pillar',
                    'severity' => 'critical',
                    'item' => $pillarName,
                    'issue' => "Pengait katrol: {$hook->label()}",
                ];
            }
            if ($tie?->isCritical()) {
                $findings[] = [
                    'category' => 'rack_pillar',
                    'severity' => 'critical',
                    'item' => $pillarName,
                    'issue' => "Ikatan: {$tie->label()}",
                ];
            }
        }

        return $findings;
    }
}
