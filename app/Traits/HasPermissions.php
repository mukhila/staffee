<?php

namespace App\Traits;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Facades\Cache;

trait HasPermissions
{
    // ─── Core check ───────────────────────────────────────────────────────────

    /**
     * Admin bypasses everything. Others: look up their role's permission slugs (cached).
     */
    public function hasPermission(string $slug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        return in_array($slug, $this->cachedPermissionSlugs());
    }

    /**
     * True if the user has ALL listed permissions.
     */
    public function hasAllPermissions(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if (!$this->hasPermission($slug)) {
                return false;
            }
        }
        return true;
    }

    /**
     * True if the user has ANY of the listed permissions.
     */
    public function hasAnyPermission(array $slugs): bool
    {
        foreach ($slugs as $slug) {
            if ($this->hasPermission($slug)) {
                return true;
            }
        }
        return false;
    }

    // ─── Cache ────────────────────────────────────────────────────────────────

    /**
     * Returns the flat array of permission slugs for this user's role.
     * Cached per role slug so all users sharing a role share one cache entry.
     */
    public function cachedPermissionSlugs(): array
    {
        $cacheKey = "role_permissions:{$this->role}";

        return Cache::remember($cacheKey, now()->addHour(), function () {
            $role = Role::where('slug', $this->role)->with('permissions')->first();
            return $role ? $role->permissions->pluck('slug')->toArray() : [];
        });
    }

    /**
     * Bust the permission cache for this user's role.
     * Call this whenever a role's permissions are changed.
     */
    public function flushPermissionCache(): void
    {
        Cache::forget("role_permissions:{$this->role}");
    }

    /**
     * Static helper: flush cache for a specific role slug.
     */
    public static function flushPermissionCacheForRole(string $roleSlug): void
    {
        Cache::forget("role_permissions:{$roleSlug}");
    }

    // ─── Mutation helpers ─────────────────────────────────────────────────────

    /**
     * Grant a permission directly to the role this user belongs to.
     * Intended for seeding / programmatic use; flushes cache automatically.
     */
    public function givePermissionTo(string $slug): void
    {
        $role = Role::where('slug', $this->role)->first();
        if (!$role) {
            return;
        }
        $permission = Permission::where('slug', $slug)->first();
        if ($permission && !$role->permissions->contains($permission->id)) {
            $role->permissions()->attach($permission->id);
        }
        $this->flushPermissionCache();
    }

    /**
     * Revoke a permission from the role this user belongs to.
     */
    public function revokePermissionTo(string $slug): void
    {
        $role = Role::where('slug', $this->role)->first();
        if (!$role) {
            return;
        }
        $permission = Permission::where('slug', $slug)->first();
        if ($permission) {
            $role->permissions()->detach($permission->id);
        }
        $this->flushPermissionCache();
    }

    // ─── Convenience wrappers ─────────────────────────────────────────────────

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff';
    }

    public function isPm(): bool
    {
        return $this->role === 'pm';
    }

    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    public function hasAnyRole(array $roles): bool
    {
        return in_array($this->role, $roles);
    }
}
