<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailLog;
use App\Services\Email\EmailService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EmailLogController extends Controller
{
    public function __construct(private readonly EmailService $emails) {}

    public function index(Request $request)
    {
        $query = EmailLog::query()->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('template_key', $request->type);
        }
        if ($request->filled('search')) {
            $query->where('recipient', 'like', '%' . $request->search . '%');
        }
        if ($request->filled('from')) {
            $query->whereDate('created_at', '>=', $request->from);
        }
        if ($request->filled('to')) {
            $query->whereDate('created_at', '<=', $request->to);
        }

        $logs = $query->paginate(20)->withQueryString()->through(fn ($l) => [
            'id'           => $l->id,
            'template_key' => $l->template_key,
            'recipient'    => $l->recipient,
            'subject'      => $l->subject,
            'status'       => $l->status,
            'error'        => $l->error,
            'order_id'     => $l->order_id,
            'created_at'   => $l->created_at?->format('d M Y, h:i A'),
            'sent_at'      => $l->sent_at?->format('d M Y, h:i A'),
        ]);

        $types = array_merge(
            array_keys($this->emails->registry()),
            EmailLog::distinct()->pluck('template_key')->filter()->all(),
        );

        return Inertia::render('Admin/Email/Logs', [
            'logs'    => $logs,
            'filters' => $request->only(['status', 'type', 'search', 'from', 'to']),
            'types'   => array_values(array_unique($types)),
        ]);
    }

    public function resend(EmailLog $log)
    {
        $this->emails->resend($log);

        return back()->with('success', 'Email re-queued for ' . $log->recipient . '.');
    }

    /** Delete all email logs. */
    public function clear()
    {
        EmailLog::truncate();

        return back()->with('success', 'Email logs cleared.');
    }
}
