<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class PermissionService
{
    private const CACHE_TTL = 3600; // 1 hour

    // ─── Role-level lookups ───────────────────────────────────────────────────

    /**
     * All permission slugs for a role — cached per role slug.
     * This is the same cache key used by HasPermissions::cachedPermissionSlugs()
     * so both paths share one warm entry.
     */
    public function slugsForRole(string $roleSlug): array
    {
        return Cache::remember(
            "role_permissions:{$roleSlug}",
            self::CACHE_TTL,
            function () use ($roleSlug) {
                $role = Role::where('slug', $roleSlug)->with('permissions')->first();
                return $role ? $role->permissions->pluck('slug')->toArray() : [];
            }
        );
    }

    /**
     * Check whether a given role carries a permission.
     */
    public function roleHasPermission(string $roleSlug, string $permissionSlug): bool
    {
        if ($roleSlug === 'admin') {
            return true;
        }
        return in_array($permissionSlug, $this->slugsForRole($roleSlug));
    }

    // ─── Matrix data ──────────────────────────────────────────────────────────

    /**
     * Returns [role_id => [permission_id => true]] for the matrix view / AJAX.
     * Cached separately because it spans all roles at once.
     */
    public function matrixData(): array
    {
        return Cache::remember('permission_matrix', self::CACHE_TTL, function () {
            $matrix = [];
            Role::with('permissions')->get()->each(function (Role $role) use (&$matrix) {
                $matrix[$role->id] = $role->permissions->pluck('id')->flip()->toArray();
            });
            return $matrix;
        });
    }

    /**
     * Permissions grouped by category, ready for the matrix/edit views.
     */
    public function allByCategory(): Collection
    {
        return Cache::remember('permissions_by_category', self::CACHE_TTL, function () {
            return Permission::orderBy('category')->orderBy('name')->get()->groupBy('category');
        });
    }

    // ─── Cache management ─────────────────────────────────────────────────────

    public function flushAll(): void
    {
        Role::all()->each(fn (Role $r) => Cache::forget("role_permissions:{$r->slug}"));
        Cache::forget('permission_matrix');
        Cache::forget('permissions_by_category');
    }

    public function flushRole(string $roleSlug): void
    {
        Cache::forget("role_permissions:{$roleSlug}");
        Cache::forget('permission_matrix');
    }
}
