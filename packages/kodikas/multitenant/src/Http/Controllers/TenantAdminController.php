<?php

namespace Kodikas\Multitenant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kodikas\Multitenant\Facades\Tenant as TenantFacade;
use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Models\TenantInvitation;
use Kodikas\Multitenant\Models\TenantUser;

class TenantAdminController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth', 'tenant.identify', 'tenant.access:permission:manage_tenant']);
    }

    /**
     * Display tenant dashboard.
     */
    public function dashboard()
    {
        $tenant = TenantFacade::current();

        $stats = [
            'total_users' => $tenant->users()->count(),
            'active_users' => $tenant->users()->wherePivot('status', TenantUser::STATUS_ACTIVE)->count(),
            'employees' => $tenant->users()->wherePivot('user_type', TenantUser::TYPE_EMPLOYEE)->count(),
            'clients' => $tenant->users()->wherePivot('user_type', TenantUser::TYPE_CLIENT)->count(),
            'vendors' => $tenant->users()->wherePivot('user_type', TenantUser::TYPE_VENDOR)->count(),
            'pending_invitations' => $tenant->pendingInvitations()->count(),
            'subscription_status' => $tenant->subscriptionActive() ? 'active' : 'inactive',
            'plan' => $tenant->plan,
            'limits' => $tenant->getLimits(),
        ];

        $recentUsers = $tenant->users()
            ->wherePivot('status', TenantUser::STATUS_ACTIVE)
            ->orderByPivot('joined_at', 'desc')
            ->limit(5)
            ->get();

        return view('multitenant::admin.dashboard', compact('tenant', 'stats', 'recentUsers'));
    }

    /**
     * Display tenant users.
     */
    public function users(Request $request)
    {
        $tenant = TenantFacade::current();

        $query = $tenant->users();

        // Filtros
        if ($request->filled('user_type')) {
            $query->wherePivot('user_type', $request->user_type);
        }

        if ($request->filled('role')) {
            $query->wherePivot('role', $request->role);
        }

        if ($request->filled('status')) {
            $query->wherePivot('status', $request->status);
        }

        $users = $query->paginate(15);

        $userTypes = [
            TenantUser::TYPE_OWNER => 'Propietario',
            TenantUser::TYPE_ADMIN => 'Administrador',
            TenantUser::TYPE_EMPLOYEE => 'Empleado',
            TenantUser::TYPE_CLIENT => 'Cliente',
            TenantUser::TYPE_VENDOR => 'Proveedor',
            TenantUser::TYPE_PARTNER => 'Socio',
            TenantUser::TYPE_CONSULTANT => 'Consultor',
            TenantUser::TYPE_GUEST => 'Invitado',
        ];

        $roles = [
            TenantUser::ROLE_SUPER_ADMIN => 'Super Administrador',
            TenantUser::ROLE_ADMIN => 'Administrador',
            TenantUser::ROLE_MANAGER => 'Gerente',
            TenantUser::ROLE_EMPLOYEE => 'Empleado',
            TenantUser::ROLE_CLIENT => 'Cliente',
            TenantUser::ROLE_VIEWER => 'Observador',
        ];

        return view('multitenant::admin.users.index', compact('users', 'userTypes', 'roles', 'tenant'));
    }

    /**
     * Show form to invite user.
     */
    public function inviteUser()
    {
        $tenant = TenantFacade::current();

        // Verificar límites
        $currentUsers = $tenant->users()->count();
        if (! $tenant->canPerform('users', $currentUsers)) {
            return back()->with('error', 'Has alcanzado el límite de usuarios para tu plan actual.');
        }

        $userTypes = [
            TenantUser::TYPE_ADMIN => 'Administrador',
            TenantUser::TYPE_EMPLOYEE => 'Empleado',
            TenantUser::TYPE_CLIENT => 'Cliente',
            TenantUser::TYPE_VENDOR => 'Proveedor',
            TenantUser::TYPE_PARTNER => 'Socio',
            TenantUser::TYPE_CONSULTANT => 'Consultor',
        ];

        $roles = [
            TenantUser::ROLE_ADMIN => 'Administrador',
            TenantUser::ROLE_MANAGER => 'Gerente',
            TenantUser::ROLE_EMPLOYEE => 'Empleado',
            TenantUser::ROLE_CLIENT => 'Cliente',
            TenantUser::ROLE_VIEWER => 'Observador',
        ];

        return view('multitenant::admin.users.invite', compact('userTypes', 'roles', 'tenant'));
    }

    /**
     * Send user invitation.
     */
    public function sendInvitation(Request $request)
    {
        $tenant = TenantFacade::current();

        $request->validate([
            'email' => 'required|email',
            'role' => 'required|in:'.implode(',', array_keys([
                TenantUser::ROLE_ADMIN => 'Administrador',
                TenantUser::ROLE_MANAGER => 'Gerente',
                TenantUser::ROLE_EMPLOYEE => 'Empleado',
                TenantUser::ROLE_CLIENT => 'Cliente',
                TenantUser::ROLE_VIEWER => 'Observador',
            ])),
            'user_type' => 'required|in:'.implode(',', [
                TenantUser::TYPE_ADMIN,
                TenantUser::TYPE_EMPLOYEE,
                TenantUser::TYPE_CLIENT,
                TenantUser::TYPE_VENDOR,
                TenantUser::TYPE_PARTNER,
                TenantUser::TYPE_CONSULTANT,
            ]),
            'message' => 'nullable|string|max:500',
        ]);

        // Verificar si ya existe invitación pendiente
        $existingInvitation = $tenant->invitations()
            ->where('email', $request->email)
            ->where('status', TenantInvitation::STATUS_PENDING)
            ->first();

        if ($existingInvitation) {
            return back()->with('error', 'Ya existe una invitación pendiente para este email.');
        }

        // Verificar límites
        $currentUsers = $tenant->users()->count();
        if (! $tenant->canPerform('users', $currentUsers)) {
            return back()->with('error', 'Has alcanzado el límite de usuarios para tu plan actual.');
        }

        $invitation = TenantInvitation::create([
            'tenant_id' => $tenant->id,
            'email' => $request->email,
            'role' => $request->role,
            'invited_by' => auth()->id(),
            'message' => $request->message,
        ]);

        // Aquí se enviaría el email de invitación
        // event(new UserInvited($invitation));

        return redirect()->route('tenant.admin.users')
            ->with('success', 'Invitación enviada exitosamente a '.$request->email);
    }

    /**
     * Update user in tenant.
     */
    public function updateUser(Request $request, $userId)
    {
        $tenant = TenantFacade::current();

        $request->validate([
            'user_type' => 'required|in:'.implode(',', [
                TenantUser::TYPE_ADMIN,
                TenantUser::TYPE_EMPLOYEE,
                TenantUser::TYPE_CLIENT,
                TenantUser::TYPE_VENDOR,
                TenantUser::TYPE_PARTNER,
                TenantUser::TYPE_CONSULTANT,
            ]),
            'role' => 'required|in:'.implode(',', [
                TenantUser::ROLE_ADMIN,
                TenantUser::ROLE_MANAGER,
                TenantUser::ROLE_EMPLOYEE,
                TenantUser::ROLE_CLIENT,
                TenantUser::ROLE_VIEWER,
            ]),
            'status' => 'required|in:'.implode(',', [
                TenantUser::STATUS_ACTIVE,
                TenantUser::STATUS_INACTIVE,
                TenantUser::STATUS_SUSPENDED,
            ]),
            'permissions' => 'array',
            'access_restrictions' => 'array',
        ]);

        $tenantUser = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // Verificar que no se puede cambiar el tipo del propietario
        if ($tenantUser->user_type === TenantUser::TYPE_OWNER) {
            return back()->with('error', 'No se puede modificar el tipo del propietario del tenant.');
        }

        $tenantUser->update([
            'user_type' => $request->user_type,
            'role' => $request->role,
            'status' => $request->status,
            'permissions' => $request->permissions ?? [],
            'access_restrictions' => $request->access_restrictions ?? [],
        ]);

        return back()->with('success', 'Usuario actualizado exitosamente.');
    }

    /**
     * Remove user from tenant.
     */
    public function removeUser($userId)
    {
        $tenant = TenantFacade::current();

        $tenantUser = TenantUser::where('tenant_id', $tenant->id)
            ->where('user_id', $userId)
            ->firstOrFail();

        // No se puede eliminar al propietario
        if ($tenantUser->user_type === TenantUser::TYPE_OWNER) {
            return back()->with('error', 'No se puede eliminar al propietario del tenant.');
        }

        $tenantUser->delete();

        return back()->with('success', 'Usuario eliminado del tenant exitosamente.');
    }

    /**
     * Display tenant settings.
     */
    public function settings()
    {
        $tenant = TenantFacade::current();

        return view('multitenant::admin.settings', compact('tenant'));
    }

    /**
     * Update tenant settings.
     */
    public function updateSettings(Request $request)
    {
        $tenant = TenantFacade::current();

        $request->validate([
            'name' => 'required|string|max:255',
            'settings.app_name' => 'nullable|string|max:255',
            'settings.locale' => 'nullable|in:es,en',
            'settings.timezone' => 'nullable|string',
            'custom_data' => 'array',
        ]);

        $tenant->update([
            'name' => $request->name,
            'settings' => array_merge($tenant->settings ?? [], $request->settings ?? []),
            'custom_data' => array_merge($tenant->custom_data ?? [], $request->custom_data ?? []),
        ]);

        return back()->with('success', 'Configuración actualizada exitosamente.');
    }

    /**
     * Display billing information.
     */
    public function billing()
    {
        $tenant = TenantFacade::current();

        $plans = config('multitenant.billing.plans', []);

        return view('multitenant::admin.billing', compact('tenant', 'plans'));
    }

    /**
     * Display access logs.
     */
    public function accessLogs(Request $request)
    {
        $tenant = TenantFacade::current();

        $logs = TenantUser::where('tenant_id', $tenant->id)
            ->whereNotNull('last_access_at')
            ->with('user')
            ->orderBy('last_access_at', 'desc');

        if ($request->filled('user_type')) {
            $logs->where('user_type', $request->user_type);
        }

        $logs = $logs->paginate(20);

        return view('multitenant::admin.access-logs', compact('logs', 'tenant'));
    }

    /**
     * Display tenant analytics.
     */
    public function analytics()
    {
        $tenant = TenantFacade::current();

        // Estadísticas por tipo de usuario
        $userStats = TenantUser::where('tenant_id', $tenant->id)
            ->selectRaw('user_type, COUNT(*) as count')
            ->groupBy('user_type')
            ->get()
            ->pluck('count', 'user_type');

        // Actividad reciente
        $recentActivity = TenantUser::where('tenant_id', $tenant->id)
            ->whereNotNull('last_access_at')
            ->where('last_access_at', '>=', now()->subDays(30))
            ->count();

        return view('multitenant::admin.analytics', compact('tenant', 'userStats', 'recentActivity'));
    }
}
