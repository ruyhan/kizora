<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatbotEvent extends Model
{
    public $timestamps = false;

    protected $fillable = ['conversation_id', 'type', 'product_id', 'order_id', 'query', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public static function track(string $type, array $attrs = []): void
    {
        static::create(array_merge(['type' => $type, 'created_at' => now()], $attrs));
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
