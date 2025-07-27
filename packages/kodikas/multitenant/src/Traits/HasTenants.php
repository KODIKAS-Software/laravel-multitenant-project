<?php

namespace Kodikas\Multitenant\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Models\TenantInvitation;
use Kodikas\Multitenant\Models\TenantUser;

trait HasTenants
{
    /**
     * Get all tenants for this user.
     */
    public function tenants(): BelongsToMany
    {
        return $this->belongsToMany(Tenant::class, 'tenant_users')
            ->using(TenantUser::class)
            ->withPivot([
                'user_type', 'role', 'status', 'permissions',
                'invited_by', 'invited_at', 'joined_at', 'last_access_at',
                'access_restrictions', 'custom_data',
            ])
            ->withTimestamps();
    }

    /**
     * Get tenant user relationships.
     */
    public function tenantUsers(): HasMany
    {
        return $this->hasMany(TenantUser::class);
    }

    /**
     * Get active tenants for this user.
     */
    public function activeTenants(): BelongsToMany
    {
        return $this->tenants()
            ->wherePivot('status', TenantUser::STATUS_ACTIVE)
            ->where('tenants.status', Tenant::STATUS_ACTIVE);
    }

    /**
     * Get tenants where user is owner.
     */
    public function ownedTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivot('user_type', TenantUser::TYPE_OWNER);
    }

    /**
     * Get tenants where user is admin.
     */
    public function adminTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivot('role', [
            TenantUser::ROLE_SUPER_ADMIN,
            TenantUser::ROLE_ADMIN,
        ]);
    }

    /**
     * Get tenants where user is employee.
     */
    public function employeeTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivot('user_type', TenantUser::TYPE_EMPLOYEE);
    }

    /**
     * Get tenants where user is client.
     */
    public function clientTenants(): BelongsToMany
    {
        return $this->tenants()->wherePivot('user_type', TenantUser::TYPE_CLIENT);
    }

    /**
     * Get pending invitations for this user.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class, 'email', 'email')
            ->where('status', TenantInvitation::STATUS_PENDING);
    }

    /**
     * Check if user belongs to tenant.
     */
    public function belongsToTenant(Tenant $tenant): bool
    {
        return $this->tenants()->where('tenants.id', $tenant->id)->exists();
    }

    /**
     * Check if user can access tenant.
     */
    public function canAccessTenant(Tenant $tenant): bool
    {
        $tenantUser = $this->getTenantUser($tenant);

        if (! $tenantUser) {
            return false;
        }

        return $tenantUser->canAccess();
    }

    /**
     * Get tenant user relationship.
     */
    public function getTenantUser(Tenant $tenant): ?TenantUser
    {
        return TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $this->id)
            ->first();
    }

    /**
     * Check if user has permission in tenant.
     */
    public function hasPermissionInTenant(Tenant $tenant, string $permission): bool
    {
        $tenantUser = $this->getTenantUser($tenant);

        if (! $tenantUser) {
            return false;
        }

        return $tenantUser->hasPermission($permission);
    }

    /**
     * Check if user can perform action in tenant.
     */
    public function canPerformInTenant(Tenant $tenant, string $action, array $context = []): bool
    {
        $tenantUser = $this->getTenantUser($tenant);

        if (! $tenantUser) {
            return false;
        }

        return $tenantUser->canPerform($action, $context);
    }

    /**
     * Get user type in tenant.
     */
    public function getUserTypeInTenant(Tenant $tenant): ?string
    {
        $tenantUser = $this->getTenantUser($tenant);

        return $tenantUser ? $tenantUser->user_type : null;
    }

    /**
     * Get user role in tenant.
     */
    public function getRoleInTenant(Tenant $tenant): ?string
    {
        $tenantUser = $this->getTenantUser($tenant);

        return $tenantUser ? $tenantUser->role : null;
    }

    /**
     * Check if user is owner of tenant.
     */
    public function isOwnerOfTenant(Tenant $tenant): bool
    {
        $tenantUser = $this->getTenantUser($tenant);

        return $tenantUser && $tenantUser->isOwner();
    }

    /**
     * Check if user is admin in tenant.
     */
    public function isAdminInTenant(Tenant $tenant): bool
    {
        $tenantUser = $this->getTenantUser($tenant);

        return $tenantUser && $tenantUser->isAdmin();
    }

    /**
     * Check if user is employee in tenant.
     */
    public function isEmployeeInTenant(Tenant $tenant): bool
    {
        $tenantUser = $this->getTenantUser($tenant);

        return $tenantUser && $tenantUser->isEmployee();
    }

    /**
     * Check if user is client in tenant.
     */
    public function isClientInTenant(Tenant $tenant): bool
    {
        $tenantUser = $this->getTenantUser($tenant);

        return $tenantUser && $tenantUser->isClient();
    }

    /**
     * Join tenant with specific role and type.
     */
    public function joinTenant(Tenant $tenant, string $userType, string $role, array $permissions = []): TenantUser
    {
        return TenantUser::create([
            'tenant_id' => $tenant->id,
            'user_id' => $this->id,
            'user_type' => $userType,
            'role' => $role,
            'status' => TenantUser::STATUS_ACTIVE,
            'permissions' => $permissions,
            'joined_at' => now(),
        ]);
    }

    /**
     * Leave tenant.
     */
    public function leaveTenant(Tenant $tenant): bool
    {
        return TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $this->id)
            ->delete();
    }

    /**
     * Update last access for tenant.
     */
    public function updateTenantAccess(Tenant $tenant): void
    {
        $tenantUser = $this->getTenantUser($tenant);

        if ($tenantUser) {
            $tenantUser->updateLastAccess();
        }
    }

    /**
     * Get primary tenant (first owned or admin tenant).
     */
    public function getPrimaryTenant(): ?Tenant
    {
        // Primero buscar tenants propios
        $ownedTenant = $this->ownedTenants()->first();
        if ($ownedTenant) {
            return $ownedTenant;
        }

        // Luego buscar tenants donde es admin
        $adminTenant = $this->adminTenants()->first();
        if ($adminTenant) {
            return $adminTenant;
        }

        // Finalmente cualquier tenant activo
        return $this->activeTenants()->first();
    }

    /**
     * Switch to specific tenant context.
     */
    public function switchToTenant(Tenant $tenant): bool
    {
        if (! $this->canAccessTenant($tenant)) {
            return false;
        }

        app('tenant')->set($tenant);
        $this->updateTenantAccess($tenant);

        return true;
    }

    /**
     * Get tenant access statistics.
     */
    public function getTenantAccessStats(Tenant $tenant): array
    {
        $tenantUser = $this->getTenantUser($tenant);

        if (! $tenantUser) {
            return [];
        }

        return [
            'user_type' => $tenantUser->user_type,
            'role' => $tenantUser->role,
            'status' => $tenantUser->status,
            'joined_at' => $tenantUser->joined_at,
            'last_access_at' => $tenantUser->last_access_at,
            'permissions_count' => count($tenantUser->permissions ?? []),
            'hierarchy_level' => $tenantUser->getHierarchyLevel(),
        ];
    }
}
