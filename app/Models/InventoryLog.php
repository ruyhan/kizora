<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'product_id', 'user_id', 'user_name', 'previous_stock',
        'added_quantity', 'new_stock', 'note', 'created_at',
    ];

    protected $casts = ['created_at' => 'datetime'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
