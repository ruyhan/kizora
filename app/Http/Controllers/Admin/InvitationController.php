<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\UserInvitationMail;
use App\Models\AuditLog;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class InvitationController extends Controller
{
    public function store(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'unique:users,email', Rule::unique('user_invitations', 'email')->whereNull('accepted_at')],
            'role_id' => ['required', Rule::exists('roles', 'id')->where('is_staff', true)],
        ]);

        $invite = UserInvitation::create([
            'email' => $data['email'],
            'role_id' => $data['role_id'],
            'token' => UserInvitation::generateToken(),
            'invited_by' => $request->user()->id,
            'expires_at' => now()->addDays(7),
        ]);

        AuditLog::record('invited', 'Users', "Invited {$invite->email}");

        $link = route('invite.accept', $invite->token);

        // Send the invitation email. Failures (e.g. mail mis-config) never block
        // the workflow — the link is always returned as a fallback to share.
        $emailed = true;
        try {
            Mail::to($invite->email)->send(new UserInvitationMail($link, $invite->role?->name, $request->user()->name));
        } catch (\Throwable $e) {
            report($e);
            $emailed = false;
        }

        return back()
            ->with('success', $emailed ? "Invitation emailed to {$invite->email}." : 'Invitation created (email failed — share the link).')
            ->with('invite_link', $link);
    }

    public function destroy(UserInvitation $invitation)
    {
        $invitation->delete();
        return back()->with('success', 'Invitation revoked.');
    }
}
