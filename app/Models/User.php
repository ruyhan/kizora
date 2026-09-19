<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name', 'email', 'phone', 'password', 'role_id', 'avatar',
        'status', 'last_login_at', 'delivery_area_id', 'delivery_address',
    ];

    protected $hidden = [
        'password', 'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password' => 'hashed',
        ];
    }

    /** Cached per-request permission slugs. */
    private ?array $permissionCache = null;

    /**
     * Send the password-reset link using the store's branded, editable email
     * template (logged + SMTP-aware). Falls back to Laravel's default mail if
     * the email system is disabled or the template is off, so reset never breaks.
     */
    public function sendPasswordResetNotification($token): void
    {
        $url = route('password.reset', ['token' => $token]) . '?email=' . urlencode($this->email);

        $sent = app(\App\Services\Email\EmailService::class)->send('password_reset', $this->email, [
            'customer_name' => $this->name,
            'reset_link'    => $url,
        ]);

        if (! $sent) {
            $this->notify(new \Illuminate\Auth\Notifications\ResetPassword($token));
        }
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role?->slug === 'super_admin';
    }

    /** Can this user reach the admin panel at all? (staff roles only) */
    public function canAccessAdmin(): bool
    {
        return $this->status === 'active' && (bool) ($this->role?->is_staff);
    }

    /** Backward-compatible: historically gated the whole admin panel. */
    public function isAdmin(): bool
    {
        return $this->canAccessAdmin();
    }

    public function isConsumer(): bool
    {
        return $this->role?->slug === 'consumer' || is_null($this->role_id);
    }

    /** Flat list of permission slugs this user holds (Super Admin = all). */
    public function permissionSlugs(): array
    {
        if ($this->permissionCache !== null) {
            return $this->permissionCache;
        }

        if ($this->isSuperAdmin()) {
            return $this->permissionCache = Permission::pluck('slug')->all();
        }

        return $this->permissionCache = $this->role
            ? $this->role->permissions->pluck('slug')->all()
            : [];
    }

    public function hasPermission(string $slug): bool
    {
        return $this->isSuperAdmin() || in_array($slug, $this->permissionSlugs(), true);
    }

    public function hasAnyPermission(array $slugs): bool
    {
        return $this->isSuperAdmin() || (bool) array_intersect($slugs, $this->permissionSlugs());
    }

    public function hasRole(string $slug): bool
    {
        return $this->role?->slug === $slug;
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function privilegeMembership()
    {
        return $this->hasOne(PrivilegeMembership::class);
    }

    public function deliveryArea()
    {
        return $this->belongsTo(DeliveryArea::class);
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function cart()
    {
        return $this->hasMany(Cart::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
}
