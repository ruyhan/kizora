<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Order;
use App\Models\PrivilegeMembership;
use App\Models\User;
use App\Services\Email\EmailService;
use App\Services\PrivilegeClaimService;
use App\Support\PrivilegeCardSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Customer side of the Privilege Membership: apply, track status, view the virtual card. */
class PrivilegeController extends Controller
{
    public function __construct(
        private readonly PrivilegeCardSettings $settings,
        private readonly PrivilegeClaimService $claims,
    ) {}

    /** One-click activation of a card an admin issued to this email address. */
    public function claim(Request $request, PrivilegeMembership $membership)
    {
        $user = Auth::user();

        if (! $request->hasValidSignature()) {
            return redirect()->route('account.privilege')->with('error', 'That activation link has expired. Send yourself a new one below.');
        }
        if (! $membership->isUnclaimed() || ! hash_equals((string) $membership->email, (string) $user->email)) {
            return redirect()->route('account.privilege')->with('error', 'This activation link is not for your account.');
        }
        if ($user->privilegeMembership()->exists()) {
            return redirect()->route('account.privilege')->with('error', 'Your account already has a membership.');
        }

        $this->claims->link($membership, $user);

        return redirect()->route('account.privilege')->with('success', 'Your Privilege Card is now active.');
    }

    /** Re-send the activation email for a card issued to this address. */
    public function resendClaim()
    {
        $user = Auth::user();
        $membership = $this->claims->pendingFor($user);
        abort_unless($membership, 404);

        return $this->claims->sendLink($membership, $user)
            ? back()->with('success', "Activation link sent to {$user->email}.")
            : back()->with('error', 'We just sent a link — please check your inbox (and spam) before asking for another.');
    }

    /**
     * A customer qualifies after one completed order worth at least the configured
     * amount. Returns [qualified, biggest completed order, requirement].
     *
     * @return array{0:bool,1:float,2:float}
     */
    private function eligibility(User $user): array
    {
        $required = $this->settings->minOrderToApply();
        if ($required <= 0) {
            return [true, 0.0, 0.0];
        }
        $best = (float) Order::where('user_id', $user->id)->where('status', 'completed')->max('total');

        return [$best >= $required, $best, $required];
    }

    public function show(): Response
    {
        $user = Auth::user();
        $m = $user->privilegeMembership;
        $s = $this->settings->get();
        [$qualified, $best, $required] = $this->eligibility($user);
        // A card an admin issued to this email, waiting for the activation link to be clicked.
        $unclaimed = $m ? null : $this->claims->pendingFor($user);

        return Inertia::render('Account/Privilege', [
            'awaitingActivation' => $unclaimed ? [
                'email'     => $user->email,
                'sent_at'   => $unclaimed->claim_sent_at?->format('d M Y, h:i A'),
                'link_days' => \App\Services\PrivilegeClaimService::LINK_DAYS,
            ] : null,
            'eligibility' => [
                'qualified'   => $qualified,
                'required'    => $required,
                'best_order'  => $best,
            ],
            'membership' => $m ? [
                'status'      => $m->status,
                'card_number' => $m->isApproved() ? PrivilegeMembership::formatNumber($m->card_number) : null,
                'applied_at'  => $m->applied_at?->format('d M Y'),
                'approved_at' => $m->approved_at?->format('d M Y'),
                'rejected_at' => $m->rejected_at?->format('d M Y'),
                'admin_note'  => $m->admin_note,
                'phone'       => $m->phone,
            ] : null,
            'holder' => ['name' => $user->name, 'phone' => $user->phone, 'email' => $user->email],
            'card'   => $this->settings->cardContent(),
            'program' => [
                'title'     => $s['program_title'],
                'intro'     => $s['program_intro'],
                'benefits'  => array_values($s['benefits']),
                'accepting' => (bool) $s['accepting_applications'],
                // Member discount % (0 = none) and whether guests can use the card number at checkout.
                'discount'    => $this->settings->discountPercent(),
                'guest_field' => $this->settings->guestFieldEnabled(),
            ],
        ]);
    }

    public function apply(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'phone' => ['required', new \App\Rules\BdPhone],
            'note'  => 'nullable|string|max:500',
            'agree' => 'accepted',
        ], ['agree.accepted' => 'Please accept the Terms & Conditions.']);

        if (! $this->settings->get()['accepting_applications']) {
            return back()->with('error', 'Applications are currently closed. Please check back soon.');
        }

        $m = $user->privilegeMembership;
        if ($m && in_array($m->status, ['pending', 'approved'], true)) {
            return back()->with('error', $m->status === 'approved' ? 'You are already a Privilege Member.' : 'Your application is already under review.');
        }

        [$qualified, , $required] = $this->eligibility($user);
        if (! $qualified) {
            return back()->with('error', 'You can apply once you have a completed order of ৳' . number_format($required) . ' or more.');
        }

        $payload = [
            'status'      => 'pending',
            'email'       => $user->email,
            'phone'       => $data['phone'],
            'note'        => $data['note'] ?? null,
            'admin_note'  => null,
            'applied_at'  => now(),
            'rejected_at' => null,
        ];
        $m ? $m->update($payload) : $user->privilegeMembership()->create($payload);

        if (! $user->phone) {
            $user->update(['phone' => $data['phone']]);
        }
        AuditLog::record('applied', 'Privilege Membership', "{$user->name} applied for Privilege Membership");

        rescue(fn () => app(EmailService::class)->send('membership_applied', $user->email, [
            'customer_name'   => $user->name,
            'membership_link' => route('account.privilege'),
        ]), report: true);

        return back()->with('success', 'Application submitted! We will notify you once it is reviewed.');
    }

    /** Withdraw a pending application. */
    public function cancel()
    {
        $m = Auth::user()->privilegeMembership;
        abort_unless($m && $m->status === 'pending', 422);

        // A returning member (card already issued) goes back to "revoked"; a first-timer's row is removed.
        $m->card_number ? $m->update(['status' => 'revoked']) : $m->delete();

        return back()->with('success', 'Your application has been withdrawn.');
    }
}
