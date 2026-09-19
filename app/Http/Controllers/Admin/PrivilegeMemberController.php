<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\PrivilegeMembership;
use App\Services\Email\EmailService;
use App\Support\PrivilegeCardSettings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PrivilegeMemberController extends Controller
{
    public function __construct(private readonly PrivilegeCardSettings $settings) {}

    /** Requests list — defaults to the pending queue. */
    public function index(Request $request): Response
    {
        $status = $request->get('status', 'pending');
        $query = PrivilegeMembership::query()->with('user:id,name,email,phone,avatar', 'reviewer:id,name');

        if (in_array($status, PrivilegeMembership::STATUSES, true)) {
            $query->where('status', $status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $digits = preg_replace('/\s+/', '', $s);
            $query->where(fn ($q) => $q
                ->where('card_number', 'like', "%{$digits}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")));
        }

        $status === 'pending'
            ? $query->orderBy('applied_at')          // oldest request first
            : $query->orderByDesc('updated_at');

        $members = $query->paginate(15)->withQueryString()->through(fn (PrivilegeMembership $m) => [
            'id'          => $m->id,
            'status'      => $m->status,
            'name'        => $m->user?->name,
            'email'       => $m->user?->email,
            'avatar'      => $m->user?->avatar,
            'phone'       => $m->phone ?: $m->user?->phone,
            'note'        => $m->note,
            'admin_note'  => $m->admin_note,
            'card_number' => PrivilegeMembership::formatNumber($m->card_number),
            'applied_at'  => $m->applied_at?->format('d M Y, h:i A'),
            'approved_at' => $m->approved_at?->format('d M Y'),
            'rejected_at' => $m->rejected_at?->format('d M Y'),
            'reviewer'    => $m->reviewer?->name,
        ]);

        $counts = PrivilegeMembership::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c', 'status');

        return Inertia::render('Admin/PrivilegeMembers/Index', [
            'members' => $members,
            'filters' => ['status' => $status, 'search' => $request->get('search', '')],
            'counts'  => [
                'pending'  => (int) ($counts['pending'] ?? 0),
                'approved' => (int) ($counts['approved'] ?? 0),
                'rejected' => (int) ($counts['rejected'] ?? 0),
                'revoked'  => (int) ($counts['revoked'] ?? 0),
                'all'      => (int) $counts->sum(),
            ],
            'card' => $this->settings->cardContent(),
        ]);
    }

    public function approve(PrivilegeMembership $membership, EmailService $email)
    {
        abort_if($membership->status === 'approved', 422, 'Already approved.');

        DB::transaction(function () use ($membership) {
            // A returning member keeps the card number they were issued before.
            if (! $membership->card_number) {
                [$serial, $number] = $this->nextCardNumber();
                $membership->serial = $serial;
                $membership->card_number = $number;
            }
            $membership->fill([
                'status'      => 'approved',
                'approved_at' => now(),
                'rejected_at' => null,
                'admin_note'  => null,
                'reviewed_by' => auth()->id(),
            ])->save();
        });

        $user = $membership->user;
        AuditLog::record('approved', 'Privilege Membership', "Approved {$user?->name} — card {$membership->card_number}");

        rescue(fn () => $email->send('membership_approved', $user?->email, [
            'customer_name'   => $user?->name,
            'card_number'     => PrivilegeMembership::formatNumber($membership->card_number),
            'membership_link' => route('account.privilege'),
        ]), report: true);

        return back()->with('success', "Approved — card {$this->fmt($membership)} issued to {$user?->name}.");
    }

    public function reject(Request $request, PrivilegeMembership $membership, EmailService $email)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);
        abort_unless($membership->status === 'pending', 422, 'Only pending requests can be rejected.');

        $membership->update([
            'status'      => 'rejected',
            'rejected_at' => now(),
            'admin_note'  => $data['reason'] ?? null,
            'reviewed_by' => auth()->id(),
        ]);

        $user = $membership->user;
        AuditLog::record('rejected', 'Privilege Membership', "Rejected {$user?->name}");

        rescue(fn () => $email->send('membership_rejected', $user?->email, [
            'customer_name' => $user?->name,
            'reason'        => $data['reason'] ?: 'Your application does not meet our current membership criteria.',
        ]), report: true);

        return back()->with('success', 'Request rejected.');
    }

    /** Suspend an active member. The card number stays reserved for them. */
    public function revoke(Request $request, PrivilegeMembership $membership)
    {
        $data = $request->validate(['reason' => 'nullable|string|max:500']);
        abort_unless($membership->status === 'approved', 422, 'Only active members can be revoked.');

        $membership->update([
            'status'      => 'revoked',
            'admin_note'  => $data['reason'] ?? null,
            'reviewed_by' => auth()->id(),
        ]);
        AuditLog::record('revoked', 'Privilege Membership', "Revoked {$membership->user?->name} — card {$membership->card_number}");

        return back()->with('success', 'Membership revoked.');
    }

    public function destroy(PrivilegeMembership $membership)
    {
        $name = $membership->user?->name;
        $membership->delete();
        AuditLog::record('deleted', 'Privilege Membership', "Deleted membership record of {$name}");

        return back()->with('success', 'Membership record deleted.');
    }

    // ── Card settings ────────────────────────────────────────────────────────

    public function settings(): Response
    {
        $s = $this->settings->get();
        [, $next] = $this->nextCardNumber();

        return Inertia::render('Admin/PrivilegeMembers/Settings', [
            'settings'   => $s,
            'nextNumber' => PrivilegeMembership::formatNumber($next),
            'issued'     => PrivilegeMembership::whereNotNull('card_number')->count(),
        ]);
    }

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'tier_line_1'            => 'required|string|max:30',
            'tier_line_2'            => 'nullable|string|max:30',
            'terms_heading'          => 'required|string|max:60',
            'terms'                  => 'array|max:10',
            'terms.*'                => 'nullable|string|max:140',
            'number_prefix'          => 'required|digits_between:4,12',
            'serial_digits'          => 'required|integer|min:3|max:8',
            'serial_start'           => 'required|integer|min:1',
            'accepting_applications' => 'boolean',
            'program_title'          => 'required|string|max:80',
            'program_intro'          => 'nullable|string|max:500',
            'benefits'               => 'array|max:12',
            'benefits.*'             => 'nullable|string|max:120',
        ]);

        $data['terms'] = array_values(array_filter(array_map('trim', $data['terms'] ?? []), 'strlen'));
        $data['benefits'] = array_values(array_filter(array_map('trim', $data['benefits'] ?? []), 'strlen'));

        $this->settings->save($data);
        AuditLog::record('updated', 'Privilege Membership', 'Updated privilege card settings');

        return back()->with('success', 'Card settings saved.');
    }

    /** @return array{0:int,1:string} [serial, raw card number] for the next approval. */
    private function nextCardNumber(): array
    {
        $s = $this->settings->get();
        $max = (int) PrivilegeMembership::lockForUpdate()->max('serial');
        $serial = max($max + 1, (int) $s['serial_start']);
        $number = $s['number_prefix'] . str_pad((string) $serial, (int) $s['serial_digits'], '0', STR_PAD_LEFT);

        // Guard against a prefix change colliding with an already-issued number.
        while (PrivilegeMembership::where('card_number', $number)->exists()) {
            $serial++;
            $number = $s['number_prefix'] . str_pad((string) $serial, (int) $s['serial_digits'], '0', STR_PAD_LEFT);
        }

        return [$serial, $number];
    }

    private function fmt(PrivilegeMembership $m): string
    {
        return (string) PrivilegeMembership::formatNumber($m->card_number);
    }
}
