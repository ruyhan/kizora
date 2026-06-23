<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'slug', 'description', 'is_system', 'is_staff'];

    protected $casts = [
        'is_system' => 'boolean',
        'is_staff'  => 'boolean',
    ];

    public function permissions()
    {
        return $this->belongsToMany(Permission::class);
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->slug === 'super_admin';
    }

    /** Super Admin implicitly holds every permission. */
    public function hasPermission(string $slug): bool
    {
        return $this->isSuperAdmin() || $this->permissions->contains('slug', $slug);
    }
}
