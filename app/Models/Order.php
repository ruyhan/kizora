<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number', 'source', 'user_id', 'guest_name', 'guest_email', 'guest_phone',
        'delivery_address', 'delivery_city', 'delivery_area', 'delivery_postal_code',
        'status', 'subtotal', 'delivery_charge',
        'discount', 'total', 'coupon_code', 'payment_method', 'payment_status',
        'notes', 'estimated_delivery',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'delivery_charge' => 'decimal:2',
        'discount' => 'decimal:2',
        'total' => 'decimal:2',
        'estimated_delivery' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        // A temporary unique value satisfies the NOT NULL + unique constraint at insert time…
        static::creating(function ($order) {
            if (! $order->order_number) {
                $order->order_number = 'TMP-' . Str::uuid();
            }
        });

        // …then the canonical, globally-unique ORD-#### is derived from the
        // auto-increment id (atomic, no duplicates under concurrency).
        static::created(function ($order) {
            if (str_starts_with((string) $order->order_number, 'TMP-')) {
                $order->order_number = self::makeNumber($order->id);
                $order->saveQuietly(); // no events → no audit noise / recursion
            }
        });
    }

    /** Canonical order number. Single source of truth, never changes after creation. */
    public static function makeNumber(int $id): string
    {
        return 'ORD-' . (10000 + $id);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function invoice()
    {
        return $this->hasOne(Invoice::class);
    }

    public function isPos(): bool
    {
        return $this->source === 'pos';
    }

    public function scopePos($query)
    {
        return $query->where('source', 'pos');
    }

    public function scopeChatbot($query)
    {
        return $query->where('source', 'chatbot');
    }

    public function scopeWeb($query)
    {
        // Website orders only — POS & chatbot orders are managed in their own sections.
        return $query->where(fn ($q) => $q->whereNull('source')->orWhereNotIn('source', ['pos', 'chatbot']));
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending',
            'confirmed' => 'Confirmed',
            'processing' => 'Processing',
            'out_for_delivery' => 'Out for Delivery',
            'completed' => 'Completed',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'confirmed' => 'primary',
            'processing' => 'purple',
            'out_for_delivery' => 'blue',
            'completed' => 'success',
            'cancelled' => 'error',
            default => 'muted',
        };
    }
}
