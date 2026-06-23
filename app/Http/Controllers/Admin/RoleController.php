<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class RoleController extends Controller
{
    public function index(): Response
    {
        $roles = Role::withCount('users')->with('permissions:id,slug')->orderByDesc('is_staff')->orderBy('name')
            ->get()->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
                'description' => $r->description,
                'is_system' => $r->is_system,
                'is_staff' => $r->is_staff,
                'users_count' => $r->users_count,
                'permissions' => $r->permissions->pluck('slug')->all(),
            ]);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
            // Permission catalogue grouped by module for the matrix UI.
            'catalogue' => collect(config('permissions'))->map(fn ($perms, $module) => [
                'module' => $module,
                'permissions' => collect($perms)->map(fn ($label, $slug) => ['slug' => $slug, 'label' => $label])->values(),
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:60|unique:roles,name',
            'description' => 'nullable|string|max:255',
            'is_staff' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        $role = Role::create([
            'name' => $data['name'],
            'slug' => $this->uniqueSlug($data['name']),
            'description' => $data['description'] ?? null,
            'is_system' => false,
            'is_staff' => $data['is_staff'] ?? true,
        ]);
        $role->permissions()->sync($this->permissionIds($data['permissions'] ?? []));
        AuditLog::record('created', 'Roles', "Created role {$role->name}");

        return back()->with('success', 'Role created.');
    }

    public function update(Request $request, Role $role)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:60', Rule::unique('roles', 'name')->ignore($role->id)],
            'description' => 'nullable|string|max:255',
            'is_staff' => 'boolean',
            'permissions' => 'array',
            'permissions.*' => 'string',
        ]);

        // System role identity is protected; permissions stay editable (except Super Admin = all).
        $update = ['description' => $data['description'] ?? null];
        if (! $role->is_system) {
            $update['name'] = $data['name'];
            $update['is_staff'] = $data['is_staff'] ?? $role->is_staff;
        }
        $role->update($update);

        if ($role->slug !== 'super_admin') {
            $role->permissions()->sync($this->permissionIds($data['permissions'] ?? []));
        }
        AuditLog::record('updated', 'Roles', "Updated role {$role->name} & permissions");

        return back()->with('success', 'Role updated.');
    }

    public function duplicate(Role $role)
    {
        $copy = Role::create([
            'name' => $this->copyName($role->name),
            'slug' => $this->uniqueSlug($role->name . ' copy'),
            'description' => $role->description,
            'is_system' => false,
            'is_staff' => $role->is_staff,
        ]);
        $copy->permissions()->sync($role->permissions->pluck('id'));
        AuditLog::record('duplicated', 'Roles', "Duplicated {$role->name}");

        return back()->with('success', 'Role duplicated.');
    }

    /** Roles the system depends on and can never be removed. */
    public const PROTECTED = ['super_admin', 'consumer'];

    public function destroy(Role $role)
    {
        if (in_array($role->slug, self::PROTECTED, true)) {
            return back()->with('error', 'This role is protected and cannot be deleted.');
        }
        // Move any users on this role to Consumer to avoid orphaning.
        $consumer = Role::where('slug', 'consumer')->first();
        $role->users()->update(['role_id' => $consumer?->id]);
        $name = $role->name;
        $role->delete();
        AuditLog::record('deleted', 'Roles', "Deleted role {$name}");

        return back()->with('success', 'Role deleted.');
    }

    private function permissionIds(array $slugs): array
    {
        return Permission::whereIn('slug', $slugs)->pluck('id')->all();
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name, '_');
        $slug = $base;
        $i = 2;
        while (Role::where('slug', $slug)->exists()) {
            $slug = $base . '_' . $i++;
        }
        return $slug;
    }

    private function copyName(string $name): string
    {
        $base = $name . ' (Copy)';
        $n = $base;
        $i = 2;
        while (Role::where('name', $n)->exists()) {
            $n = $name . " (Copy {$i})";
            $i++;
        }
        return $n;
    }
}
