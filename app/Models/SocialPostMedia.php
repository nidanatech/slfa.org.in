<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use App\Traits\BelongsToTenant;

class SocialPostMedia extends Model
{
    use SoftDeletes, BelongsToTenant;

    protected $fillable = [
        'social_post_id',
        'file_path',
        'file_type',
        'file_size',
        'mime_type',
        'original_filename',
        'width',
        'height',
        'duration',
        'ai_generated',
        'ai_prompt',
        'sort_order',
    ];

    protected $casts = [
        'ai_generated' => 'boolean',
        'file_size' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
        'duration' => 'integer',
        'sort_order' => 'integer',
    ];

    // Relationships
    public function post(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class, 'social_post_id');
    }

    // Accessors
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFullPathAttribute(): string
    {
        return Storage::disk('public')->path($this->file_path);
    }

    public function getSizeInMbAttribute(): float
    {
        return round($this->file_size / 1024 / 1024, 2);
    }

    // Helper Methods
    public function isImage(): bool
    {
        return $this->file_type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->file_type === 'video';
    }

    public function deleteFile(): bool
    {
        if (Storage::disk('public')->exists($this->file_path)) {
            return Storage::disk('public')->delete($this->file_path);
        }
        return false;
    }

    // Events
    protected static function booted(): void
    {
        static::deleting(function (SocialPostMedia $media) {
            $media->deleteFile();
        });
    }
}
