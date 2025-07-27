<?php

namespace Kodikas\Multitenant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Kodikas\Multitenant\Facades\Tenant;

class TenantAccessControlMiddleware
{
    /**
     * Handle an incoming request with advanced access control.
     */
    public function handle(Request $request, Closure $next, ...$parameters)
    {
        // Verificar que tenemos un tenant
        if (!Tenant::check()) {
            return $this->handleNoTenant($request);
        }

        $tenant = Tenant::current();
        $user = $request->user();

        // Si no hay usuario autenticado, continuar (para rutas públicas)
        if (!$user) {
            return $next($request);
        }

        // Obtener relación tenant-usuario
        $tenantUser = $user->getTenantUser($tenant);

        if (!$tenantUser) {
            return $this->handleUserNotInTenant($request, $user, $tenant);
        }

        // Verificar acceso básico
        if (!$tenantUser->canAccess()) {
            return $this->handleAccessDenied($request, $tenantUser, 'basic_access');
        }

        // Verificar restricciones específicas del middleware
        if (!$this->checkMiddlewareRestrictions($tenantUser, $parameters)) {
            return $this->handleAccessDenied($request, $tenantUser, 'middleware_restrictions');
        }

        // Actualizar último acceso
        $tenantUser->updateLastAccess();

        // Agregar información del usuario al request
        $request->merge([
            'tenant_user' => $tenantUser,
            'user_type' => $tenantUser->user_type,
            'user_role' => $tenantUser->role,
        ]);

        return $next($request);
    }

    /**
     * Check middleware-specific restrictions.
     */
    protected function checkMiddlewareRestrictions($tenantUser, array $parameters): bool
    {
        foreach ($parameters as $parameter) {
            if (!$this->checkParameter($tenantUser, $parameter)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check individual parameter restriction.
     */
    protected function checkParameter($tenantUser, string $parameter): bool
    {
        // Verificar tipos de usuario permitidos
        if (str_starts_with($parameter, 'type:')) {
            $allowedType = substr($parameter, 5);
            return $tenantUser->user_type === $allowedType;
        }

        // Verificar roles permitidos
        if (str_starts_with($parameter, 'role:')) {
            $allowedRole = substr($parameter, 5);
            return $tenantUser->role === $allowedRole;
        }

        // Verificar permisos específicos
        if (str_starts_with($parameter, 'permission:')) {
            $permission = substr($parameter, 11);
            return $tenantUser->hasPermission($permission);
        }

        // Verificar nivel de jerarquía mínimo
        if (str_starts_with($parameter, 'level:')) {
            $minLevel = (int) substr($parameter, 6);
            return $tenantUser->getHierarchyLevel() >= $minLevel;
        }

        // Verificar que no sea cliente (para rutas administrativas)
        if ($parameter === 'not_client') {
            return !$tenantUser->isClient();
        }

        // Verificar que sea empleado interno
        if ($parameter === 'internal_only') {
            return in_array($tenantUser->user_type, [
                'owner', 'admin', 'employee', 'manager'
            ]);
        }

        // Verificar suscripción activa
        if ($parameter === 'subscription_required') {
            return $tenantUser->tenant->subscriptionActive() || $tenantUser->tenant->onTrial();
        }

        // Verificar que el tenant esté en plan específico
        if (str_starts_with($parameter, 'plan:')) {
            $requiredPlan = substr($parameter, 5);
            return $tenantUser->tenant->plan === $requiredPlan;
        }

        return true;
    }

    /**
     * Handle no tenant found.
     */
    protected function handleNoTenant(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Tenant required',
                'message' => 'No tenant context available for this request'
            ], 404);
        }

        return redirect()->route('tenant.select');
    }

    /**
     * Handle user not in tenant.
     */
    protected function handleUserNotInTenant(Request $request, $user, $tenant)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access denied',
                'message' => 'User does not have access to this tenant'
            ], 403);
        }

        return response()->view('multitenant::errors.user-not-in-tenant', [
            'user' => $user,
            'tenant' => $tenant
        ], 403);
    }

    /**
     * Handle access denied.
     */
    protected function handleAccessDenied(Request $request, $tenantUser, string $reason)
    {
        $messages = [
            'basic_access' => 'Your access to this tenant has been restricted',
            'middleware_restrictions' => 'You do not have the required permissions for this action',
        ];

        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Access denied',
                'message' => $messages[$reason] ?? 'Access denied',
                'user_type' => $tenantUser->user_type,
                'role' => $tenantUser->role,
            ], 403);
        }

        return response()->view('multitenant::errors.access-denied', [
            'tenant_user' => $tenantUser,
            'reason' => $reason,
            'message' => $messages[$reason] ?? 'Access denied'
        ], 403);
    }
}
