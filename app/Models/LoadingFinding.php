<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoadingFinding extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_session_id',
        'category',
        'severity',
        'item_name',
        'finding_type',
        'description',
        'corrective_action',
        'photo',
        'status',
        'resolved_at',
        'resolved_by',
        'resolution_notes',
        'escalated',
        'escalated_to',
        'escalated_at',
        'created_by',
    ];

    protected $casts = [
        'escalated' => 'boolean',
        'resolved_at' => 'datetime',
        'escalated_at' => 'datetime',
    ];

    // Relationships
    public function loadingSession(): BelongsTo
    {
        return $this->belongsTo(LoadingSession::class);
    }

    public function resolvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function escalatedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_to');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeOpen($query)
    {
        return $query->where('status', 'open');
    }

    public function scopeInProgress($query)
    {
        return $query->where('status', 'in_progress');
    }

    public function scopeResolved($query)
    {
        return $query->where('status', 'resolved');
    }

    public function scopeCritical($query)
    {
        return $query->where('severity', 'critical');
    }

    public function scopeWarning($query)
    {
        return $query->where('severity', 'warning');
    }

    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    // Business Logic
    public function resolve(User $user, ?string $notes = null): void
    {
        $this->status = 'resolved';
        $this->resolved_by = $user->id;
        $this->resolved_at = now();
        $this->resolution_notes = $notes;
        $this->save();
    }

    public function escalate(User $to, ?string $reason = null): void
    {
        $this->escalated = true;
        $this->escalated_to = $to->id;
        $this->escalated_at = now();
        if ($reason) {
            $this->description .= "\n\n[ESCALATED]: {$reason}";
        }
        $this->save();
    }

    public function startProgress(): void
    {
        if ($this->status === 'open') {
            $this->status = 'in_progress';
            $this->save();
        }
    }

    public function close(): void
    {
        $this->status = 'closed';
        $this->save();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isCritical(): bool
    {
        return $this->severity === 'critical';
    }

    public function getSeverityColor(): string
    {
        return match ($this->severity) {
            'critical' => 'danger',
            'warning' => 'warning',
            'info' => 'info',
            default => 'gray',
        };
    }

    public function getStatusColor(): string
    {
        return match ($this->status) {
            'open' => 'danger',
            'in_progress' => 'warning',
            'resolved' => 'success',
            'closed' => 'gray',
            default => 'gray',
        };
    }
}
