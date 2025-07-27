<?php

namespace Kodikas\Multitenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kodikas\Multitenant\Facades\Tenant;

class IdentifyTenantMiddleware
{
    /**
     * Handle an incoming request to identify the tenant.
     */
    public function handle(Request $request, Closure $next)
    {
        // The tenant resolver already runs during service provider boot
        // This middleware ensures we have tenant context for routes that need it

        $tenant = Tenant::current();

        if ($tenant) {
            // Add tenant info to view data
            view()->share('currentTenant', $tenant);

            // Set tenant-specific configuration
            $this->configureTenantSettings($tenant);
        }

        return $next($request);
    }

    /**
     * Configure tenant-specific settings.
     */
    protected function configureTenantSettings($tenant): void
    {
        // Set tenant-specific app name
        if (isset($tenant->settings['app_name'])) {
            config(['app.name' => $tenant->settings['app_name']]);
        }

        // Set tenant-specific locale
        if (isset($tenant->settings['locale'])) {
            app()->setLocale($tenant->settings['locale']);
        }

        // Set tenant-specific timezone
        if (isset($tenant->settings['timezone'])) {
            config(['app.timezone' => $tenant->settings['timezone']]);
        }

        // Set tenant-specific mail configuration
        if (isset($tenant->settings['mail'])) {
            config(['mail' => array_merge(config('mail'), $tenant->settings['mail'])]);
        }
    }
}
