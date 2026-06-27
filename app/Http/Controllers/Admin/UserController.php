<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserController extends Controller
{
    /** Staff users only (consumers live under Customers). */
    private function staffQuery()
    {
        return User::query()->whereHas('role', fn ($q) => $q->where('is_staff', true));
    }

    public function index(Request $request): Response
    {
        $dr = \App\Support\DateRange::fromRequest($request, 'all');
        $query = $dr->applyTo($this->staffQuery()->with('role'));

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn ($q) => $q->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('phone', 'like', "%{$s}%"));
        }
        if ($request->filled('role')) {
            $query->whereHas('role', fn ($q) => $q->where('slug', $request->role));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $users = $query->latest()->paginate(15)->withQueryString()->through(fn (User $u) => [
            'id' => $u->id,
            'name' => $u->name,
            'email' => $u->email,
            'phone' => $u->phone,
            'avatar' => $u->avatar,
            'status' => $u->status,
            'role' => $u->role ? ['slug' => $u->role->slug, 'name' => $u->role->name] : null,
            'email_verified' => (bool) $u->email_verified_at,
            'last_login_at' => $u->last_login_at?->diffForHumans(),
            'created_at' => $u->created_at->format('d M Y'),
        ]);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'roles' => Role::where('is_staff', true)->get(['id', 'name', 'slug']),
            'filters' => array_merge($request->only(['search', 'role', 'status']), $dr->filters()),
            'invitations' => UserInvitation::with('role:id,name')->whereNull('accepted_at')->latest()->get()
                ->map(fn ($i) => [
                    'id' => $i->id,
                    'email' => $i->email,
                    'role' => $i->role?->name,
                    'expired' => $i->expires_at && $i->expires_at->isPast(),
                    'created_at' => $i->created_at->format('d M Y'),
                ]),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
            'password' => 'required|string|min:8',
            'role_id' => ['required', Rule::exists('roles', 'id')->where('is_staff', true)],
            'status' => 'required|in:active,inactive,suspended',
            'avatar' => 'nullable|string|max:1000',
        ]);
        $data['password'] = Hash::make($data['password']);
        $data['email_verified_at'] = now();

        User::create($data);
        return back()->with('success', 'User created.');
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'role_id' => ['required', Rule::exists('roles', 'id')->where('is_staff', true)],
            'status' => 'required|in:active,inactive,suspended',
            'avatar' => 'nullable|string|max:1000',
            'password' => 'nullable|string|min:8',
        ]);

        if (! empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        return back()->with('success', 'User updated.');
    }

    public function updateStatus(Request $request, User $user)
    {
        $request->validate(['status' => 'required|in:active,inactive,suspended']);
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change your own status.');
        }
        $user->update(['status' => $request->status]);
        AuditLog::record('status', 'Users', "Set {$user->name} to {$request->status}");
        return back()->with('success', 'Status updated.');
    }

    public function resetPassword(Request $request, User $user)
    {
        $request->validate(['password' => 'required|string|min:8']);
        $user->update(['password' => Hash::make($request->password)]);
        AuditLog::record('reset_password', 'Users', "Reset password for {$user->name}");
        return back()->with('success', 'Password reset.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete your own account.');
        }
        if ($user->isSuperAdmin() && User::whereHas('role', fn ($q) => $q->where('slug', 'super_admin'))->count() <= 1) {
            return back()->with('error', 'Cannot delete the last Super Admin.');
        }
        $user->delete();
        return back()->with('success', 'User deleted.');
    }

    public function bulk(Request $request)
    {
        $data = $request->validate([
            'action' => 'required|in:activate,suspend,delete',
            'ids' => 'required|array',
            'ids.*' => 'integer',
        ]);
        $ids = array_diff($data['ids'], [$request->user()->id]); // never act on self
        $users = $this->staffQuery()->whereIn('id', $ids);

        match ($data['action']) {
            'activate' => $users->update(['status' => 'active']),
            'suspend'  => $users->update(['status' => 'suspended']),
            'delete'   => $users->where('role_id', '!=', optional(Role::where('slug', 'super_admin')->first())->id)->delete(),
        };
        AuditLog::record('bulk_' . $data['action'], 'Users', count($ids) . ' user(s)');
        return back()->with('success', 'Bulk action applied.');
    }

    public function export(): StreamedResponse
    {
        AuditLog::record('export', 'Users', 'Exported users CSV');
        $users = $this->staffQuery()->with('role')->get();

        return response()->streamDownload(function () use ($users) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Name', 'Email', 'Phone', 'Role', 'Status', 'Verified', 'Created']);
            foreach ($users as $u) {
                fputcsv($out, [$u->name, $u->email, $u->phone, $u->role?->name, $u->status,
                    $u->email_verified_at ? 'Yes' : 'No', $u->created_at->format('Y-m-d')]);
            }
            fclose($out);
        }, 'users-' . now()->format('Y-m-d') . '.csv', ['Content-Type' => 'text/csv']);
    }
}
