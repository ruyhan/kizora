<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrivilegeMembership extends Model
{
    public const STATUSES = ['pending', 'approved', 'rejected', 'revoked'];

    protected $fillable = [
        'user_id', 'status', 'phone', 'note', 'card_number', 'serial', 'admin_note',
        'applied_at', 'approved_at', 'rejected_at', 'reviewed_by',
    ];

    protected function casts(): array
    {
        return [
            'applied_at'  => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'serial'      => 'integer',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    /** "2606151021" + "00001" → "2606 1510 2100001" (matches the card artwork). */
    public static function formatNumber(?string $raw): ?string
    {
        if (! $raw) {
            return null;
        }
        $raw = preg_replace('/\s+/', '', $raw);
        return trim(substr($raw, 0, 4) . ' ' . substr($raw, 4, 4) . ' ' . substr($raw, 8));
    }
}
