<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\ContactMessage;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

class MessageController extends Controller
{
    public function contacts(Request $request)
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo(ContactMessage::query())->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%")
                ->orWhere('phone', 'like', "%{$s}%")
                ->orWhere('subject', 'like', "%{$s}%")
                ->orWhere('message', 'like', "%{$s}%"));
        }

        $messages = $query->paginate(15)->withQueryString()->through(fn (ContactMessage $m) => [
            'id' => $m->id,
            'name' => $m->name,
            'email' => $m->email,
            'phone' => $m->phone,
            'subject' => $m->subject,
            'message' => $m->message,
            'status' => $m->status,
            'is_read' => $m->is_read,
            'created_at' => $m->created_at->format('d M Y, h:i A'),
            'created_ago' => $m->created_at->diffForHumans(),
        ]);

        // Pipeline counts for the filter tabs + stat cards.
        $byStatus = ContactMessage::selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $counts = ['all' => (int) $byStatus->sum(), 'unread' => ContactMessage::where('is_read', false)->count()];
        foreach (ContactMessage::STATUSES as $s) {
            $counts[$s] = (int) ($byStatus[$s] ?? 0);
        }

        return Inertia::render('Admin/Messages/Contacts', [
            'messages' => $messages,
            'filters' => array_merge($request->only(['status', 'search']), $dr->filters()),
            'counts' => $counts,
            'statuses' => ContactMessage::STATUSES,
        ]);
    }

    public function markContactRead(ContactMessage $message)
    {
        $message->update(['is_read' => true]);

        return back();
    }

    public function updateStatus(Request $request, ContactMessage $message)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(ContactMessage::STATUSES)],
        ]);

        $from = $message->status;
        $message->update(['status' => $data['status'], 'is_read' => true]);
        AuditLog::record('status', 'Leads', "Lead #{$message->id} ({$message->name}): {$from} → {$data['status']}");

        return back()->with('success', 'Lead status updated.');
    }

    public function destroy(ContactMessage $message)
    {
        $name = $message->name;
        $message->delete();
        AuditLog::record('deleted', 'Leads', "Lead from {$name} deleted");

        return back()->with('success', 'Lead deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:contact_messages,id',
        ]);

        $count = ContactMessage::whereIn('id', $data['ids'])->delete();
        AuditLog::record('deleted', 'Leads', "{$count} leads bulk-deleted");

        return back()->with('success', "{$count} lead(s) deleted.");
    }
}
