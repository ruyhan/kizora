<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryCity extends Model
{
    protected $fillable = ['name', 'is_active', 'delivery_charge'];

    protected $casts = [
        'is_active'       => 'boolean',
        'delivery_charge' => 'decimal:2',
    ];

    public function areas()
    {
        return $this->hasMany(DeliveryArea::class)->orderBy('name');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
