<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class UserInvitation extends Model
{
    protected $fillable = ['email', 'role_id', 'token', 'invited_by', 'expires_at', 'accepted_at'];

    protected $casts = [
        'expires_at'  => 'datetime',
        'accepted_at' => 'datetime',
    ];

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function inviter()
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public static function generateToken(): string
    {
        return Str::random(64);
    }

    public function isValid(): bool
    {
        return is_null($this->accepted_at) && (is_null($this->expires_at) || $this->expires_at->isFuture());
    }
}
