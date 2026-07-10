<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;

/**
 * Registers the Blog permission rows and grants them ONLY to the super_admin
 * and admin roles (additively — syncWithoutDetaching). Deliberately does NOT
 * run RbacSeeder or touch any other role's existing permissions.
 */
return new class extends Migration
{
    private array $perms = [
        'blog.view'   => 'View Blog',
        'blog.create' => 'Create Blog Posts',
        'blog.edit'   => 'Edit Blog Posts',
        'blog.delete' => 'Delete Blog Posts',
    ];

    public function up(): void
    {
        $ids = [];
        foreach ($this->perms as $slug => $label) {
            $ids[] = Permission::updateOrCreate(
                ['slug' => $slug],
                ['name' => $label, 'module' => 'Blog']
            )->id;
        }

        foreach (['super_admin', 'admin'] as $roleSlug) {
            $role = Role::where('slug', $roleSlug)->first();
            $role?->permissions()->syncWithoutDetaching($ids);
        }
    }

    public function down(): void
    {
        Permission::whereIn('slug', array_keys($this->perms))->delete();
    }
};
