<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCity extends Model
{
    protected $fillable = ['name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];

    public function areas()
    {
        return $this->hasMany(DeliveryArea::class)->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
