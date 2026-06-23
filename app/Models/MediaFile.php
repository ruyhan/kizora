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

    /** Full public URL */
    public function getFileUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
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
