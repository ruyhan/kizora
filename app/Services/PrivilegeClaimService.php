<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\PrivilegeMembership;
use App\Models\User;
use App\Services\Email\EmailService;
use Illuminate\Support\Facades\URL;

/**
 * Links an admin-issued membership (created against an email address) to the
 * account that signs up with that email.
 *
 * Sign-up doesn't prove the email belongs to that person, so a card is only
 * handed over once the address is confirmed: instantly for accounts that arrive
 * already verified (Google, staff invites), otherwise via a one-click link we
 * email to that address.
 */
class PrivilegeClaimService
{
    public const LINK_DAYS = 14;

    public function __construct(private readonly EmailService $email) {}

    /** The membership waiting for this account's email, if any. */
    public function pendingFor(User $user): ?PrivilegeMembership
    {
        return PrivilegeMembership::unclaimedFor($user->email)->first();
    }

    /**
     * Called after sign-up / login. Links straight away when the email is already
     * verified, otherwise sends the activation link.
     *
     * @return 'linked'|'sent'|null
     */
    public function attempt(User $user): ?string
    {
        $membership = $this->pendingFor($user);
        if (! $membership) {
            return null;
        }
        if ($user->privilegeMembership()->exists()) {
            return null;                       // the account already has its own membership
        }

        if ($user->email_verified_at) {
            $this->link($membership, $user);
            return 'linked';
        }

        // Only the first time — after that it's the customer's "resend" button, so
        // logging in every day doesn't keep mailing the same link.
        return $membership->claim_sent_at === null && $this->sendLink($membership, $user) ? 'sent' : null;
    }

    /** Emails the activation link (at most one every 10 minutes). */
    public function sendLink(PrivilegeMembership $membership, User $user, bool $force = false): bool
    {
        if (! $force && $membership->claim_sent_at && $membership->claim_sent_at->gt(now()->subMinutes(10))) {
            return false;
        }

        $membership->forceFill(['claim_sent_at' => now()])->saveQuietly();

        rescue(fn () => $this->email->send('membership_activate', $user->email, [
            'customer_name'   => $user->name,
            'card_number'     => PrivilegeMembership::formatNumber($membership->card_number),
            'activation_link' => $this->linkFor($membership),
        ]), report: true);

        return true;
    }

    public function linkFor(PrivilegeMembership $membership): string
    {
        return URL::temporarySignedRoute('account.privilege.claim', now()->addDays(self::LINK_DAYS), [
            'membership' => $membership->id,
        ]);
    }

    /** Hands the card to the account and marks the email as confirmed. */
    public function link(PrivilegeMembership $membership, User $user): void
    {
        $membership->forceFill(['user_id' => $user->id, 'claim_sent_at' => null])->save();

        if (! $user->email_verified_at) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }

        AuditLog::record('claimed', 'Privilege Membership', "{$user->email} activated card {$membership->card_number}");
    }
}
