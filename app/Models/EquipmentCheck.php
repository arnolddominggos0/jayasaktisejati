<?php

namespace App\Models;

use App\Enums\EquipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EquipmentCheck extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_session_id',
        // Katrol
        'pulley_top_status',
        'pulley_top_notes',
        'pulley_top_photo',
        'pulley_bottom_status',
        'pulley_bottom_notes',
        'pulley_bottom_photo',
        // Tali Mono
        'mono_rope_condition',
        'mono_rope_notes',
        'mono_rope_photo',
        // Rantai
        'chain_strength',
        'chain_notes',
        'chain_photo',
        // Mur/Baut
        'bolt_nut_status',
        'bolt_nut_notes',
        'bolt_nut_photo',
        // Bambu
        'bamboo_condition',
        'bamboo_notes',
        'bamboo_photo',
        // Tangga
        'ladder_stability',
        'ladder_notes',
        'ladder_photo',
        // Sponds
        'sponds_cleanliness',
        'sponds_notes',
        'sponds_photo',
        // Other
        'other_equipment',
        'other_equipment_notes',
        // Safety Summary
        'pulley_safe',
        'mono_rope_safe',
        'chain_safe',
        'bolt_nut_safe',
        'bamboo_safe',
        'ladder_safe',
        'sponds_safe',
        'overall_safe',
        'critical_issues_count',
        'warning_issues_count',
        'checked_by',
        'checked_at',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
        'pulley_safe' => 'boolean',
        'mono_rope_safe' => 'boolean',
        'chain_safe' => 'boolean',
        'bolt_nut_safe' => 'boolean',
        'bamboo_safe' => 'boolean',
        'ladder_safe' => 'boolean',
        'sponds_safe' => 'boolean',
        'overall_safe' => 'boolean',
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

    // Safety Check Methods
    public function isPulleySafe(): bool
    {
        return in_array($this->pulley_top_status, ['ok', 'present', 'tight']) &&
               in_array($this->pulley_bottom_status, ['ok', 'present', 'tight']);
    }

    public function isMonoRopeSafe(): bool
    {
        return in_array($this->mono_rope_condition, ['ok', 'new', 'strong']);
    }

    public function isChainSafe(): bool
    {
        return in_array($this->chain_strength, ['ok', 'strong', 'tight']);
    }

    public function isBoltNutSafe(): bool
    {
        return in_array($this->bolt_nut_status, ['ok', 'tight', 'present']);
    }

    public function isBambooSafe(): bool
    {
        return in_array($this->bamboo_condition, ['ok', 'thick', 'strong', 'new']);
    }

    public function isLadderSafe(): bool
    {
        return in_array($this->ladder_stability, ['ok', 'stable', 'present']);
    }

    public function isSpondsSafe(): bool
    {
        return in_array($this->sponds_cleanliness, ['ok', 'clean', 'present']);
    }

    public function isOverallSafe(): bool
    {
        return $this->isPulleySafe() &&
               $this->isMonoRopeSafe() &&
               $this->isChainSafe() &&
               $this->isBoltNutSafe() &&
               $this->isBambooSafe() &&
               $this->isLadderSafe() &&
               $this->isSpondsSafe();
    }

    // Count Issues
    public function countCriticalIssues(): int
    {
        $count = 0;
        $criticalStatuses = ['not_ok', 'loose', 'cracked', 'unstable', 'dirty', 'not_present', 'worn'];

        $fields = [
            'pulley_top_status',
            'pulley_bottom_status',
            'mono_rope_condition',
            'chain_strength',
            'bolt_nut_status',
            'bamboo_condition',
            'ladder_stability',
            'sponds_cleanliness',
        ];

        foreach ($fields as $field) {
            if (in_array($this->$field, $criticalStatuses)) {
                $count++;
            }
        }

        return $count;
    }

    public function countWarningIssues(): int
    {
        $count = 0;
        // Worn items are warnings if not critical
        if ($this->mono_rope_condition === 'worn') $count++;

        return $count;
    }

    // Update counts
    public function updateSafetyStatus(): void
    {
        $this->pulley_safe = $this->isPulleySafe();
        $this->mono_rope_safe = $this->isMonoRopeSafe();
        $this->chain_safe = $this->isChainSafe();
        $this->bolt_nut_safe = $this->isBoltNutSafe();
        $this->bamboo_safe = $this->isBambooSafe();
        $this->ladder_safe = $this->isLadderSafe();
        $this->sponds_safe = $this->isSpondsSafe();
        $this->overall_safe = $this->isOverallSafe();
        $this->critical_issues_count = $this->countCriticalIssues();
        $this->warning_issues_count = $this->countWarningIssues();
        $this->save();

        // Update parent session
        $this->loadingSession?->recalculateSafetyStatus();
    }

    // Get findings
    public function getFindings(): array
    {
        $findings = [];

        $equipmentMap = [
            ['name' => 'Katrol Atas', 'field' => 'pulley_top_status'],
            ['name' => 'Katrol Bawah', 'field' => 'pulley_bottom_status'],
            ['name' => 'Tali Mono', 'field' => 'mono_rope_condition'],
            ['name' => 'Rantai', 'field' => 'chain_strength'],
            ['name' => 'Mur/Baut', 'field' => 'bolt_nut_status'],
            ['name' => 'Bambu', 'field' => 'bamboo_condition'],
            ['name' => 'Tangga', 'field' => 'ladder_stability'],
            ['name' => 'Sponds', 'field' => 'sponds_cleanliness'],
        ];

        foreach ($equipmentMap as $equipment) {
            $status = $this->{$equipment['field']};
            if (in_array($status, ['not_ok', 'loose', 'cracked', 'unstable', 'dirty', 'not_present'])) {
                $findings[] = [
                    'category' => 'equipment',
                    'severity' => 'critical',
                    'item' => $equipment['name'],
                    'issue' => "Status: {$status}",
                ];
            } elseif ($status === 'worn') {
                $findings[] = [
                    'category' => 'equipment',
                    'severity' => 'warning',
                    'item' => $equipment['name'],
                    'issue' => 'Kondisi aus',
                ];
            }
        }

        return $findings;
    }
}
