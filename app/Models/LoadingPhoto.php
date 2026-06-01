<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class LoadingPhoto extends Model
{
    use HasFactory;

    protected $fillable = [
        'loading_session_id',
        'category',
        'sub_category',
        'file_path',
        'file_name',
        'mime_type',
        'file_size',
        'gps_latitude',
        'gps_longitude',
        'taken_at',
        'description',
        'uploaded_by',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
        'file_size' => 'integer',
    ];

    // Relationships
    public function loadingSession(): BelongsTo
    {
        return $this->belongsTo(LoadingSession::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopeByCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBySubCategory($query, $subCategory)
    {
        return $query->where('sub_category', $subCategory);
    }

    // Accessors
    public function getFileUrlAttribute(): ?string
    {
        if (! $this->file_path) {
            return null;
        }

        return Storage::url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size ?? 0;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }

    public function getGpsCoordinatesAttribute(): ?string
    {
        if ($this->gps_latitude && $this->gps_longitude) {
            return "{$this->gps_latitude}, {$this->gps_longitude}";
        }
        return null;
    }

    public function getIsImageAttribute(): bool
    {
        return str_starts_with($this->mime_type ?? '', 'image/');
    }

    // Business Logic
    public function deleteFile(): bool
    {
        if ($this->file_path && Storage::exists($this->file_path)) {
            return Storage::delete($this->file_path);
        }
        return false;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function ($photo) {
            $photo->deleteFile();
        });
    }

    // Category helpers
    public static function getCategories(): array
    {
        return [
            'mp_attendance' => 'Kehadiran MP',
            'health_check' => 'Cek Kesehatan',
            'apd' => 'APD',
            'equipment' => 'Alat',
            'rack_pillar' => 'Pilar Rack',
            'drop_floor' => 'Drop Floor',
            'container_structure' => 'Struktur Container',
            'unit' => 'Unit',
            'final' => 'Final',
            'other' => 'Lainnya',
        ];
    }

    public function getCategoryLabelAttribute(): string
    {
        return self::getCategories()[$this->category] ?? $this->category;
    }
}
