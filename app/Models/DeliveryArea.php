<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeliveryArea extends Model
{
    protected $fillable = ['delivery_city_id', 'name', 'postal_code'];

    protected $appends = ['label'];

    public function city()
    {
        return $this->belongsTo(DeliveryCity::class, 'delivery_city_id');
    }

    /** "Feni Sadar (3900)" — postal shown beside area name */
    public function getLabelAttribute(): string
    {
        return $this->postal_code ? "{$this->name} ({$this->postal_code})" : $this->name;
    }

    /** Availability is inherited from the parent city */
    public function isAvailable(): bool
    {
        return (bool) ($this->city?->is_active);
    }
}
