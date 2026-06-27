<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaFile extends Model
{
    protected $fillable = [
        'file_name', 'original_name', 'file_path', 'mime_type',
        'file_size', 'width', 'height', 'uploaded_by',
    ];

    protected $appends = ['file_url', 'human_size'];

    /**
     * Root-relative public URL (e.g. /storage/media/xxx.png).
     * Relative on purpose so picked URLs saved into CMS content work on ANY
     * domain (localhost + production) without baking in a host — avoids the
     * "site wants to access 127.0.0.1" / broken-image issue after deploy.
     */
    public function getFileUrlAttribute(): string
    {
        return '/storage/' . ltrim($this->file_path, '/');
    }

    public function getHumanSizeAttribute(): string
    {
        $bytes = (int) $this->file_size;
        if ($bytes < 1024) return "{$bytes} B";
        if ($bytes < 1024 * 1024) return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1024 / 1024, 1) . ' MB';
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
