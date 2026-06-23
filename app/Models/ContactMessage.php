<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContactMessage extends Model
{
    /** Lead pipeline statuses (ordered). */
    public const STATUSES = ['new', 'contacted', 'qualified', 'converted', 'lost'];

    protected $fillable = ['name', 'email', 'phone', 'subject', 'message', 'is_read', 'status'];

    protected $casts = ['is_read' => 'boolean'];

    protected $attributes = ['status' => 'new'];
}
