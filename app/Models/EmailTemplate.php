<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplate extends Model
{
    protected $fillable = [
        'key', 'name', 'subject', 'body_html', 'is_enabled', 'is_custom',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'is_custom'  => 'boolean',
    ];
}
