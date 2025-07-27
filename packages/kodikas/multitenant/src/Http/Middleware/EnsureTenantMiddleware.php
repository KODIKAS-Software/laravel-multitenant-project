<?php

namespace Kodikas\Multitenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kodikas\Multitenant\Facades\Tenant;
use Kodikas\Multitenant\Exceptions\TenantNotResolvedException;

class EnsureTenantMiddleware
{
    /**
     * Handle an incoming request to ensure tenant exists.
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Tenant::check()) {
            return $this->handleMissingTenant($request);
        }

        $tenant = Tenant::current();

        // Check if tenant is active
        if (!$tenant->isActive()) {
            return $this->handleInactiveTenant($request, $tenant);
        }

        // Check subscription status
        if (!$this->hasValidSubscription($tenant)) {
            return $this->handleInvalidSubscription($request, $tenant);
        }

        return $next($request);
    }

    /**
     * Handle missing tenant.
     */
    protected function handleMissingTenant(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant not found',
                'message' => 'No valid tenant could be identified for this request'
            ], 404);
        }

        // Redirect to tenant selection or central domain
        $centralDomain = config('multitenant.central_domain');
        return redirect()->to("http://{$centralDomain}");
    }

    /**
     * Handle inactive tenant.
     */
    protected function handleInactiveTenant(Request $request, $tenant)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant inactive',
                'message' => 'This tenant is currently inactive'
            ], 403);
        }

        return response()->view('multitenant::errors.tenant-inactive', compact('tenant'), 403);
    }

    /**
     * Handle invalid subscription.
     */
    protected function handleInvalidSubscription(Request $request, $tenant)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Subscription required',
                'message' => 'A valid subscription is required to access this tenant'
            ], 402);
        }

        return response()->view('multitenant::errors.subscription-required', compact('tenant'), 402);
    }

    /**
     * Check if tenant has valid subscription.
     */
    protected function hasValidSubscription($tenant): bool
    {
        if (!config('multitenant.billing.enabled')) {
            return true;
        }

        return $tenant->onTrial() || $tenant->subscriptionActive();
    }
}
