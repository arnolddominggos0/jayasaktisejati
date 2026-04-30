<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UnitCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_session_id',
        'unit_id',
        'armada_id',
        'unit_type',
        'unit_plate_number',
        // Measurements
        'distance_front_rh',
        'distance_rear_rh',
        'distance_back_door',
        'distance_rear_lh',
        'distance_front_lh',
        'drop_floor_front_height',
        'drop_floor_rear_height',
        'container_roof_distance',
        'validation_ranges',
        // Photos
        'photo_front_view',
        'photo_side_view',
        'photo_rear_view',
        'photo_top_view',
        // Results
        'measurements_valid',
        'measurement_notes',
        'unit_safe_for_loading',
        'safety_notes',
        'critical_issues_count',
        'warning_issues_count',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'validation_ranges' => 'array',
        'measurements_valid' => 'boolean',
        'unit_safe_for_loading' => 'boolean',
        'distance_front_rh' => 'integer',
        'distance_rear_rh' => 'integer',
        'distance_back_door' => 'integer',
        'distance_rear_lh' => 'integer',
        'distance_front_lh' => 'integer',
        'drop_floor_front_height' => 'integer',
        'drop_floor_rear_height' => 'integer',
        'container_roof_distance' => 'integer',
    ];

    // Relationships
    public function loadingSession(): BelongsTo
    {
        return $this->belongsTo(LoadingSession::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }

    public function armada(): BelongsTo
    {
        return $this->belongsTo(Armada::class);
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    // Default validation ranges (in cm)
    public static function getDefaultValidationRanges(): array
    {
        return [
            'distance_front_rh' => ['min' => 50, 'max' => 200],
            'distance_rear_rh' => ['min' => 50, 'max' => 200],
            'distance_back_door' => ['min' => 100, 'max' => 300],
            'distance_rear_lh' => ['min' => 50, 'max' => 200],
            'distance_front_lh' => ['min' => 50, 'max' => 200],
            'drop_floor_front_height' => ['min' => 80, 'max' => 150],
            'drop_floor_rear_height' => ['min' => 80, 'max' => 150],
            'container_roof_distance' => ['min' => 200, 'max' => 400],
        ];
    }

    // Validation Methods
    public function isDistanceFrontRhValid(): ?bool
    {
        if ($this->distance_front_rh === null) {
            return null;
        }
        $range = $this->validation_ranges['distance_front_rh'] ?? self::getDefaultValidationRanges()['distance_front_rh'];

        return $this->distance_front_rh >= $range['min'] && $this->distance_front_rh <= $range['max'];
    }

    public function isDistanceRearRhValid(): ?bool
    {
        if ($this->distance_rear_rh === null) {
            return null;
        }
        $range = $this->validation_ranges['distance_rear_rh'] ?? self::getDefaultValidationRanges()['distance_rear_rh'];

        return $this->distance_rear_rh >= $range['min'] && $this->distance_rear_rh <= $range['max'];
    }

    public function isDistanceBackDoorValid(): ?bool
    {
        if ($this->distance_back_door === null) {
            return null;
        }
        $range = $this->validation_ranges['distance_back_door'] ?? self::getDefaultValidationRanges()['distance_back_door'];

        return $this->distance_back_door >= $range['min'] && $this->distance_back_door <= $range['max'];
    }

    public function isDistanceRearLhValid(): ?bool
    {
        if ($this->distance_rear_lh === null) {
            return null;
        }
        $range = $this->validation_ranges['distance_rear_lh'] ?? self::getDefaultValidationRanges()['distance_rear_lh'];

        return $this->distance_rear_lh >= $range['min'] && $this->distance_rear_lh <= $range['max'];
    }

    public function isDistanceFrontLhValid(): ?bool
    {
        if ($this->distance_front_lh === null) {
            return null;
        }
        $range = $this->validation_ranges['distance_front_lh'] ?? self::getDefaultValidationRanges()['distance_front_lh'];

        return $this->distance_front_lh >= $range['min'] && $this->distance_front_lh <= $range['max'];
    }

    public function isDropFloorFrontHeightValid(): ?bool
    {
        if ($this->drop_floor_front_height === null) {
            return null;
        }
        $range = $this->validation_ranges['drop_floor_front_height'] ?? self::getDefaultValidationRanges()['drop_floor_front_height'];

        return $this->drop_floor_front_height >= $range['min'] && $this->drop_floor_front_height <= $range['max'];
    }

    public function isDropFloorRearHeightValid(): ?bool
    {
        if ($this->drop_floor_rear_height === null) {
            return null;
        }
        $range = $this->validation_ranges['drop_floor_rear_height'] ?? self::getDefaultValidationRanges()['drop_floor_rear_height'];

        return $this->drop_floor_rear_height >= $range['min'] && $this->drop_floor_rear_height <= $range['max'];
    }

    public function isContainerRoofDistanceValid(): ?bool
    {
        if ($this->container_roof_distance === null) {
            return null;
        }
        $range = $this->validation_ranges['container_roof_distance'] ?? self::getDefaultValidationRanges()['container_roof_distance'];

        return $this->container_roof_distance >= $range['min'] && $this->container_roof_distance <= $range['max'];
    }

    public function areAllMeasurementsValid(): bool
    {
        $checks = [
            $this->isDistanceFrontRhValid(),
            $this->isDistanceRearRhValid(),
            $this->isDistanceBackDoorValid(),
            $this->isDistanceRearLhValid(),
            $this->isDistanceFrontLhValid(),
            $this->isDropFloorFrontHeightValid(),
            $this->isDropFloorRearHeightValid(),
            $this->isContainerRoofDistanceValid(),
        ];

        // Filter out null values (not filled yet)
        $checks = array_filter($checks, fn ($v) => $v !== null);

        // If no checks done yet, return false
        if (empty($checks)) {
            return false;
        }

        return ! in_array(false, $checks, true);
    }

    public function validateMeasurements(): void
    {
        $this->measurements_valid = $this->areAllMeasurementsValid();
        $this->save();
    }

    public function countIssues(): array
    {
        $critical = 0;
        $warning = 0;

        $measurements = [
            ['name' => 'Jarak Front RH', 'valid' => $this->isDistanceFrontRhValid()],
            ['name' => 'Jarak Rear RH', 'valid' => $this->isDistanceRearRhValid()],
            ['name' => 'Jarak Back Door', 'valid' => $this->isDistanceBackDoorValid()],
            ['name' => 'Jarak Rear LH', 'valid' => $this->isDistanceRearLhValid()],
            ['name' => 'Jarak Front LH', 'valid' => $this->isDistanceFrontLhValid()],
            ['name' => 'Tinggi Drop Floor Depan', 'valid' => $this->isDropFloorFrontHeightValid()],
            ['name' => 'Tinggi Drop Floor Belakang', 'valid' => $this->isDropFloorRearHeightValid()],
            ['name' => 'Jarak Atap Container', 'valid' => $this->isContainerRoofDistanceValid()],
        ];

        foreach ($measurements as $m) {
            if ($m['valid'] === false) {
                $critical++;
            }
        }

        return ['critical' => $critical, 'warning' => $warning];
    }

    public function updateSafetyStatus(): void
    {
        $this->measurements_valid = $this->areAllMeasurementsValid();

        $issues = $this->countIssues();
        $this->critical_issues_count = $issues['critical'];
        $this->warning_issues_count = $issues['warning'];

        $this->unit_safe_for_loading = $this->measurements_valid && $this->critical_issues_count === 0;
        $this->save();

        // Update parent session
        $this->loadingSession?->recalculateSafetyStatus();
    }

    // Get findings
    public function getFindings(): array
    {
        $findings = [];

        $measurements = [
            ['name' => 'Jarak Front RH', 'field' => 'distance_front_rh', 'valid' => $this->isDistanceFrontRhValid()],
            ['name' => 'Jarak Rear RH', 'field' => 'distance_rear_rh', 'valid' => $this->isDistanceRearRhValid()],
            ['name' => 'Jarak Back Door', 'field' => 'distance_back_door', 'valid' => $this->isDistanceBackDoorValid()],
            ['name' => 'Jarak Rear LH', 'field' => 'distance_rear_lh', 'valid' => $this->isDistanceRearLhValid()],
            ['name' => 'Jarak Front LH', 'field' => 'distance_front_lh', 'valid' => $this->isDistanceFrontLhValid()],
            ['name' => 'Tinggi Drop Floor Depan', 'field' => 'drop_floor_front_height', 'valid' => $this->isDropFloorFrontHeightValid()],
            ['name' => 'Tinggi Drop Floor Belakang', 'field' => 'drop_floor_rear_height', 'valid' => $this->isDropFloorRearHeightValid()],
            ['name' => 'Jarak Atap Container', 'field' => 'container_roof_distance', 'valid' => $this->isContainerRoofDistanceValid()],
        ];

        foreach ($measurements as $m) {
            if ($m['valid'] === false) {
                $value = $this->{$m['field']};
                $range = $this->validation_ranges[$m['field']] ?? self::getDefaultValidationRanges()[$m['field']];
                $findings[] = [
                    'category' => 'unit',
                    'severity' => 'critical',
                    'item' => $m['name'],
                    'issue' => "Nilai: {$value}cm (harus antara {$range['min']}-{$range['max']}cm)",
                ];
            }
        }

        return $findings;
    }
}
