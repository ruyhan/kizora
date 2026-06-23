<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    public $timestamps = false; // only created_at

    protected $fillable = ['user_id', 'user_name', 'action', 'module', 'description', 'ip_address', 'created_at'];

    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Record an activity. No-ops when there is no authenticated actor
     * (e.g. seeders / console), so dummy data never floods the log.
     */
    public static function record(string $action, string $module, ?string $description = null): void
    {
        $user = Auth::user();
        if (! $user) {
            return;
        }

        static::create([
            'user_id'     => $user->id,
            'user_name'   => $user->name,
            'action'      => $action,
            'module'      => $module,
            'description' => $description,
            'ip_address'  => request()->ip(),
            'created_at'  => now(),
        ]);
    }
}
