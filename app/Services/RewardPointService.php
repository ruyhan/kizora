<?php

namespace App\Services;

use App\Models\Order;
use App\Models\PointTransaction;
use App\Models\User;
use App\Support\PointSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Reward points for customers WITHOUT an approved Privilege Card.
 *  - Earned when an order is completed, on the product amount actually paid.
 *  - Redeemed at checkout (1 point = point_value ৳) once the balance reaches min_redeem.
 *  - A cancelled order gives back the points it used and takes back the points it earned.
 * Every balance change goes through credit()/debit() so the ledger always matches.
 */
class RewardPointService
{
    public function __construct(private readonly PointSettings $settings) {}

    public function enabled(): bool
    {
        return (bool) $this->settings->get()['enabled'];
    }

    /** Points are for customers without an approved Privilege Card. */
    public function eligible(?User $user): bool
    {
        return $user !== null && $this->enabled() && $user->privilegeMembership()->value('status') !== 'approved';
    }

    public function pointsFor(float $amount): int
    {
        $s = $this->settings->get();
        $step = (float) $s['earn_amount'];
        return $step > 0 ? (int) floor(max(0, $amount) / $step) * (int) $s['earn_points'] : 0;
    }

    public function valueOf(int $points): float
    {
        return round($points * (float) $this->settings->get()['point_value'], 2);
    }

    /** Most points this user may spend on an order whose product amount (after other discounts) is $payable. */
    public function maxRedeemable(User $user, float $payable): int
    {
        $s = $this->settings->get();
        $value = (float) $s['point_value'];
        if (! $this->eligible($user) || $user->reward_points < (int) $s['min_redeem'] || $value <= 0) {
            return 0;
        }
        $cap = max(0, $payable) * min(100, max(0, (float) $s['max_redeem_percent'])) / 100;

        return (int) min($user->reward_points, floor($cap / $value));
    }

    /** Checks a checkout request to spend $points; returns the ৳ discount. */
    public function validateRedeem(User $user, int $points, float $payable): float
    {
        $min = (int) $this->settings->get()['min_redeem'];
        $max = $this->maxRedeemable($user, $payable);

        if ($points < $min) {
            throw ValidationException::withMessages(['redeem_points' => "You need at least {$min} points to redeem."]);
        }
        if ($points > $max) {
            throw ValidationException::withMessages(['redeem_points' => "You can use up to {$max} points on this order."]);
        }

        return $this->valueOf($points);
    }

    /** Spend points on a new order (call inside the order transaction). */
    public function redeem(User $user, Order $order, int $points): void
    {
        $this->debit($user, $points, 'redeem', $order, "Used on order {$order->order_number}", strict: true);
    }

    /** React to an order's status: award on completion, unwind on cancellation. Idempotent. */
    public function syncOrder(Order $order): void
    {
        if (! $order->user_id) {
            return;
        }

        if ($order->status === 'completed' && ! $this->logged($order, 'earn')) {
            $user = $order->user;
            $points = $this->eligible($user) ? $this->pointsFor((float) $order->subtotal - (float) $order->discount) : 0;
            if ($points > 0) {
                $this->credit($user, $points, 'earn', $order, "Earned on order {$order->order_number}");
                $order->forceFill(['points_earned' => $points])->saveQuietly();
            }
        }

        if ($order->status === 'cancelled') {
            if ($order->points_redeemed > 0 && ! $this->logged($order, 'refund')) {
                $this->credit($order->user, $order->points_redeemed, 'refund', $order, "Returned — order {$order->order_number} cancelled");
            }
            if ($order->points_earned > 0 && ! $this->logged($order, 'reverse')) {
                $this->debit($order->user, $order->points_earned, 'reverse', $order, "Taken back — order {$order->order_number} cancelled");
            }
        }
    }

    /** Manual admin correction (positive or negative). */
    public function adjust(User $user, int $points, ?string $note, ?int $adminId): void
    {
        $points > 0
            ? $this->credit($user, $points, 'adjust', null, $note ?: 'Added by admin', $adminId)
            : $this->debit($user, -$points, 'adjust', null, $note ?: 'Removed by admin', adminId: $adminId);
    }

    private function logged(Order $order, string $type): bool
    {
        return PointTransaction::where('order_id', $order->id)->where('type', $type)->exists();
    }

    private function credit(User $user, int $points, string $type, ?Order $order, string $note, ?int $adminId = null): void
    {
        DB::transaction(function () use ($user, $points, $type, $order, $note, $adminId) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            $locked->increment('reward_points', $points);
            $this->log($locked, $points, $type, $order, $note, $adminId);
            $user->reward_points = $locked->reward_points;
        });
    }

    /** strict: fail if the balance is short (checkout); otherwise take what's there (reversals). */
    private function debit(User $user, int $points, string $type, ?Order $order, string $note, bool $strict = false, ?int $adminId = null): void
    {
        DB::transaction(function () use ($user, $points, $type, $order, $note, $strict, $adminId) {
            $locked = User::whereKey($user->id)->lockForUpdate()->first();
            if ($strict && $locked->reward_points < $points) {
                throw ValidationException::withMessages(['redeem_points' => 'You no longer have enough points for this.']);
            }
            $take = min($points, $locked->reward_points);
            if ($take <= 0) {
                return;
            }
            $locked->decrement('reward_points', $take);
            $this->log($locked, -$take, $type, $order, $note, $adminId);
            $user->reward_points = $locked->reward_points;
        });
    }

    private function log(User $user, int $points, string $type, ?Order $order, string $note, ?int $adminId): void
    {
        PointTransaction::create([
            'user_id'       => $user->id,
            'order_id'      => $order?->id,
            'type'          => $type,
            'points'        => $points,
            'balance_after' => $user->reward_points,
            'note'          => $note,
            'created_by'    => $adminId,
        ]);
    }
}
