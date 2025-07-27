<?php

namespace Kodikas\Multitenant\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Kodikas\Multitenant\Models\TenantInvitation;
use Kodikas\Multitenant\Models\TenantUser;

class TenantInvitationController extends Controller
{
    /**
     * Show invitation acceptance form.
     */
    public function showAcceptForm(string $token)
    {
        $invitation = TenantInvitation::where('token', $token)->firstOrFail();

        // Verificar si la invitación es válida
        if (! $invitation->isPending()) {
            $status = $invitation->isExpired() ? 'expirada' : 'ya utilizada';

            return view('multitenant::invitations.invalid', compact('invitation', 'status'));
        }

        return view('multitenant::invitations.accept', compact('invitation'));
    }

    /**
     * Process invitation acceptance.
     */
    public function acceptInvitation(Request $request, string $token)
    {
        $invitation = TenantInvitation::where('token', $token)->firstOrFail();

        // Verificar si la invitación es válida
        if (! $invitation->isPending()) {
            return redirect()->route('login')->with('error', 'La invitación no es válida o ha expirado.');
        }

        $request->validate([
            'name' => 'required|string|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $userModel = config('multitenant.user_model');

        // Verificar si el usuario ya existe
        $user = $userModel::where('email', $invitation->email)->first();

        if (! $user) {
            // Crear nuevo usuario
            $user = $userModel::create([
                'name' => $request->name,
                'email' => $invitation->email,
                'password' => bcrypt($request->password),
                'email_verified_at' => now(),
            ]);
        }

        // Verificar si el usuario ya pertenece al tenant
        $existingTenantUser = TenantUser::where('tenant_id', $invitation->tenant_id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingTenantUser) {
            $invitation->update(['status' => TenantInvitation::STATUS_CANCELLED]);

            return redirect()->route('login')->with('error', 'Ya eres miembro de este tenant.');
        }

        // Determinar tipo de usuario basado en el rol
        $userType = $this->getUserTypeFromRole($invitation->role);

        // Determinar permisos basados en el rol
        $permissions = $this->getPermissionsFromRole($invitation->role);

        // Crear relación tenant-usuario
        TenantUser::create([
            'tenant_id' => $invitation->tenant_id,
            'user_id' => $user->id,
            'user_type' => $userType,
            'role' => $invitation->role,
            'status' => TenantUser::STATUS_ACTIVE,
            'permissions' => $permissions,
            'invited_by' => $invitation->invited_by,
            'invited_at' => $invitation->created_at,
            'joined_at' => now(),
        ]);

        // Marcar invitación como aceptada
        $invitation->accept($user);

        // Iniciar sesión del usuario
        auth()->login($user);

        return redirect()->route('tenant.dashboard')->with('success', 'Bienvenido al tenant '.$invitation->tenant->name);
    }

    /**
     * Get user type from role.
     */
    protected function getUserTypeFromRole(string $role): string
    {
        return match ($role) {
            TenantUser::ROLE_SUPER_ADMIN, TenantUser::ROLE_ADMIN => TenantUser::TYPE_ADMIN,
            TenantUser::ROLE_MANAGER => TenantUser::TYPE_EMPLOYEE,
            TenantUser::ROLE_EMPLOYEE => TenantUser::TYPE_EMPLOYEE,
            TenantUser::ROLE_CLIENT => TenantUser::TYPE_CLIENT,
            TenantUser::ROLE_VIEWER => TenantUser::TYPE_GUEST,
            default => TenantUser::TYPE_EMPLOYEE,
        };
    }

    /**
     * Get permissions from role.
     */
    protected function getPermissionsFromRole(string $role): array
    {
        return match ($role) {
            TenantUser::ROLE_SUPER_ADMIN => [
                'view_all_data',
                'manage_users',
                'manage_tenant',
                'billing_access',
                'view_dashboard',
                'view_users',
                'invite_users',
                'view_logs',
                'view_analytics',
                'export_data',
            ],
            TenantUser::ROLE_ADMIN => [
                'view_all_data',
                'manage_users',
                'view_dashboard',
                'view_users',
                'invite_users',
                'view_analytics',
                'export_data',
            ],
            TenantUser::ROLE_MANAGER => [
                'view_department_data',
                'view_dashboard',
                'view_users',
                'invite_users',
                'view_reports',
            ],
            TenantUser::ROLE_EMPLOYEE => [
                'view_dashboard',
                'view_reports',
            ],
            TenantUser::ROLE_CLIENT => [
                'view_own_data',
                'create_order',
                'access_api',
            ],
            TenantUser::ROLE_VIEWER => [
                'view_own_data',
            ],
            default => [],
        };
    }
}
