<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\PrivilegeMembership;
use App\Support\PrivilegeCardSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/** Customer side of the Privilege Membership: apply, track status, view the virtual card. */
class PrivilegeController extends Controller
{
    public function __construct(private readonly PrivilegeCardSettings $settings) {}

    public function show(): Response
    {
        $user = Auth::user();
        $m = $user->privilegeMembership;
        $s = $this->settings->get();

        return Inertia::render('Account/Privilege', [
            'membership' => $m ? [
                'status'      => $m->status,
                'card_number' => $m->isApproved() ? PrivilegeMembership::formatNumber($m->card_number) : null,
                'applied_at'  => $m->applied_at?->format('d M Y'),
                'approved_at' => $m->approved_at?->format('d M Y'),
                'rejected_at' => $m->rejected_at?->format('d M Y'),
                'admin_note'  => $m->admin_note,
                'phone'       => $m->phone,
            ] : null,
            'holder' => ['name' => $user->name, 'phone' => $user->phone],
            'card'   => $this->settings->cardContent(),
            'program' => [
                'title'     => $s['program_title'],
                'intro'     => $s['program_intro'],
                'benefits'  => array_values($s['benefits']),
                'accepting' => (bool) $s['accepting_applications'],
            ],
        ]);
    }

    public function apply(Request $request)
    {
        $user = Auth::user();
        $data = $request->validate([
            'phone' => 'required|string|max:20',
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

        $payload = [
            'status'      => 'pending',
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
