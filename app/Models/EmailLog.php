<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailLog extends Model
{
    protected $fillable = [
        'template_key', 'mailable', 'recipient', 'subject',
        'status', 'error', 'order_id', 'variables', 'sent_at',
    ];

    protected $casts = [
        'variables' => 'array',
        'sent_at'   => 'datetime',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
