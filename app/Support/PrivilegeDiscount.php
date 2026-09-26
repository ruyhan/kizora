<?php

namespace App\Support;

use App\Models\PrivilegeMembership;
use App\Models\User;

/**
 * Resolves the Privilege Member discount for a checkout. A logged-in approved
 * member gets it automatically; a guest gets it by entering an approved card
 * number (only while the admin keeps the checkout field enabled).
 */
class PrivilegeDiscount
{
    public function __construct(private readonly PrivilegeCardSettings $settings) {}

    public function percent(): float
    {
        return $this->settings->discountPercent();
    }

    /** The approved membership this checkout may use, or null. */
    public function membershipFor(?User $user, ?string $cardNumber): ?PrivilegeMembership
    {
        if ($this->percent() <= 0) {
            return null;
        }

        if ($user) {
            $m = $user->privilegeMembership;
            return $m && $m->isApproved() ? $m : null;
        }

        if (! $this->settings->guestFieldEnabled()) {
            return null;
        }

        return $this->findApprovedCard($cardNumber);
    }

    public function findApprovedCard(?string $cardNumber): ?PrivilegeMembership
    {
        $raw = preg_replace('/\D/', '', (string) $cardNumber);
        if ($raw === '') {
            return null;
        }

        return PrivilegeMembership::where('card_number', $raw)->where('status', 'approved')->first();
    }

    /**
     * Split the discount between coupon and membership.
     *
     * @return array{coupon: float, member: float, total: float, coupon_applied: bool}
     */
    public function combine(float $subtotal, float $couponDiscount, bool $isMember): array
    {
        $member = $isMember ? round($subtotal * $this->percent() / 100, 2) : 0.0;

        if ($member > 0 && $couponDiscount > 0 && ! $this->settings->get()['discount_stacks_with_coupon']) {
            // Not combinable: keep whichever saves the customer more.
            if ($couponDiscount >= $member) {
                $member = 0.0;
            } else {
                $couponDiscount = 0.0;
            }
        }

        $total = (float) min($subtotal, $couponDiscount + $member);

        return [
            'coupon'         => $couponDiscount,
            'member'         => max(0.0, $total - $couponDiscount),
            'total'          => $total,
            'coupon_applied' => $couponDiscount > 0,
        ];
    }
}
