<?php

namespace Kodikas\Multitenant;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Contracts\TenantResolverContract;

class TenantResolver implements TenantResolverContract
{
    protected $app;
    protected $request;

    public function __construct($app)
    {
        $this->app = $app;
    }

    /**
     * Boot the tenant resolver.
     */
    public function boot(): void
    {
        $this->request = $this->app['request'];

        // Auto-resolve tenant on every request
        $tenant = $this->resolve();

        if ($tenant) {
            $this->app['tenant']->set($tenant);
        }
    }

    /**
     * Resolve tenant from current request.
     */
    public function resolve(): ?Tenant
    {
        $method = config('multitenant.identification_method');

        switch ($method) {
            case 'subdomain':
                return $this->resolveFromSubdomain();

            case 'domain':
                return $this->resolveFromDomain();

            case 'path':
                return $this->resolveFromPath();

            case 'header':
                return $this->resolveFromHeader();

            case 'session':
                return $this->resolveFromSession();

            default:
                return null;
        }
    }

    /**
     * Resolve tenant from subdomain.
     */
    protected function resolveFromSubdomain(): ?Tenant
    {
        $host = $this->request->getHost();
        $centralDomain = config('multitenant.central_domain');

        // Skip if it's the central domain
        if ($host === $centralDomain) {
            return null;
        }

        $pattern = config('multitenant.subdomain_pattern');

        if (preg_match($pattern, $host, $matches)) {
            $subdomain = $matches[1];

            return $this->findTenantByIdentifier($subdomain);
        }

        return null;
    }

    /**
     * Resolve tenant from domain.
     */
    protected function resolveFromDomain(): ?Tenant
    {
        $host = $this->request->getHost();
        $centralDomain = config('multitenant.central_domain');

        // Skip if it's the central domain
        if ($host === $centralDomain) {
            return null;
        }

        return $this->findTenantByIdentifier($host);
    }

    /**
     * Resolve tenant from path.
     */
    protected function resolveFromPath(): ?Tenant
    {
        $path = $this->request->path();
        $segments = explode('/', $path);

        if (count($segments) > 0 && !empty($segments[0])) {
            $tenantSlug = $segments[0];

            return $this->findTenantByIdentifier($tenantSlug);
        }

        return null;
    }

    /**
     * Resolve tenant from header.
     */
    protected function resolveFromHeader(): ?Tenant
    {
        $tenantId = $this->request->header('X-Tenant-ID');

        if (!$tenantId) {
            return null;
        }

        return $this->findTenantByIdentifier($tenantId);
    }

    /**
     * Resolve tenant from session.
     */
    protected function resolveFromSession(): ?Tenant
    {
        if (!$this->request->hasSession()) {
            return null;
        }

        $tenantId = $this->request->session()->get('tenant_id');

        if (!$tenantId) {
            return null;
        }

        return Tenant::find($tenantId);
    }

    /**
     * Find tenant by identifier with caching.
     */
    protected function findTenantByIdentifier(string $identifier): ?Tenant
    {
        $cacheKey = config('multitenant.cache.prefix') . "identifier:{$identifier}";

        if (config('multitenant.cache.enabled')) {
            return Cache::remember($cacheKey, config('multitenant.cache.ttl'), function () use ($identifier) {
                return $this->queryTenantByIdentifier($identifier);
            });
        }

        return $this->queryTenantByIdentifier($identifier);
    }

    /**
     * Query tenant by identifier.
     */
    protected function queryTenantByIdentifier(string $identifier): ?Tenant
    {
        return Tenant::where(function ($query) use ($identifier) {
            $query->where('slug', $identifier)
                  ->orWhere('domain', $identifier)
                  ->orWhere('subdomain', $identifier);
        })
        ->where('status', Tenant::STATUS_ACTIVE)
        ->first();
    }

    /**
     * Manually set tenant by ID or slug.
     */
    public function setTenant($identifier): ?Tenant
    {
        $tenant = null;

        if (is_numeric($identifier)) {
            $tenant = Tenant::find($identifier);
        } else {
            $tenant = $this->findTenantByIdentifier($identifier);
        }

        if ($tenant) {
            $this->app['tenant']->set($tenant);

            // Store in session if using session method
            if (config('multitenant.identification_method') === 'session') {
                $this->request->session()->put('tenant_id', $tenant->id);
            }
        }

        return $tenant;
    }

    /**
     * Clear current tenant.
     */
    public function clearTenant(): void
    {
        $this->app['tenant']->forget();

        // Clear from session if using session method
        if (config('multitenant.identification_method') === 'session') {
            $this->request->session()->forget('tenant_id');
        }
    }
}
