<?php

namespace Kodikas\Multitenant\Contracts;

use Kodikas\Multitenant\Models\Tenant;

interface TenantResolverContract
{
    /**
     * Boot the tenant resolver.
     */
    public function boot(): void;

    /**
     * Resolve tenant from current request.
     */
    public function resolve(): ?Tenant;

    /**
     * Manually set tenant by ID or slug.
     */
    public function setTenant($identifier): ?Tenant;

    /**
     * Clear current tenant.
     */
    public function clearTenant(): void;
}
