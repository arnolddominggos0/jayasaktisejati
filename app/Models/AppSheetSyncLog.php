<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppSheetSyncLog extends Model
{
    use HasFactory;

    protected $table = 'app_sheet_sync_logs';\
    
    protected $fillable = [
        'sync_type',
        'table_name',
        'record_id',
        'operation',
        'payload',
        'response',
        'status',
        'error_message',
        'retry_count',
        'processed_at',
        'source',
        'synced_by',
    ];

    protected $casts = [
        'payload' => 'array',
        'response' => 'array',
        'processed_at' => 'datetime',
    ];

    // Scopes
    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeByTable($query, $tableName)
    {
        return $query->where('table_name', $tableName);
    }

    public function scopeRecent($query, $minutes = 60)
    {
        return $query->where('created_at', '>=', now()->subMinutes($minutes));
    }

    // Helper methods
    public function markAsSuccess($response = null)
    {
        $this->update([
            'status' => 'success',
            'response' => $response,
            'processed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage, $incrementRetry = true)
    {
        $data = [
            'status' => 'failed',
            'error_message' => $errorMessage,
            'processed_at' => now(),
        ];

        if ($incrementRetry) {
            $data['retry_count'] = $this->retry_count + 1;
        }

        $this->update($data);
    }

    public function shouldRetry($maxRetries = 3)
    {
        return $this->status === 'failed' && $this->retry_count < $maxRetries;
    }
}
