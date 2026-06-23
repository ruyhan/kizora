<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuditLogController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AuditLog::query()->latest();

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('description', 'like', "%{$s}%")->orWhere('user_name', 'like', "%{$s}%"));
        }
        if ($request->filled('module')) {
            $query->where('module', $request->module);
        }
        if ($request->filled('action')) {
            $query->where('action', $request->action);
        }

        $logs = $query->paginate(30)->withQueryString()->through(fn (AuditLog $l) => [
            'id' => $l->id,
            'user_name' => $l->user_name ?? 'System',
            'action' => $l->action,
            'module' => $l->module,
            'description' => $l->description,
            'ip_address' => $l->ip_address,
            'created_at' => $l->created_at?->format('d M Y, h:i A'),
        ]);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'modules' => AuditLog::distinct()->orderBy('module')->pluck('module'),
            'actions' => AuditLog::distinct()->orderBy('action')->pluck('action'),
            'filters' => $request->only(['search', 'module', 'action']),
            'total' => AuditLog::count(),
        ]);
    }

    /** Wipe the log. The clear action itself is then recorded for traceability. */
    public function clear(Request $request)
    {
        $count = AuditLog::count();
        AuditLog::query()->delete();
        AuditLog::record('cleared', 'Security', "Cleared {$count} audit log entr" . ($count === 1 ? 'y' : 'ies'));

        return back()->with('success', 'Audit logs cleared.');
    }
}
