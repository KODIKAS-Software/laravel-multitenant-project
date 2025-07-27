<?php

namespace Kodikas\Multitenant\Traits;

use Illuminate\Database\Eloquent\Builder;
use Kodikas\Multitenant\Facades\Tenant;
use Kodikas\Multitenant\Models\TenantUser;

trait HasUserTypeAccess
{
    /**
     * Boot the user type access trait.
     */
    protected static function bootHasUserTypeAccess()
    {
        // Aplicar filtros automáticos según el tipo de usuario
        static::addGlobalScope('user_type_access', function (Builder $builder) {
            static::applyUserTypeFilters($builder);
        });
    }

    /**
     * Apply user type specific filters.
     */
    protected static function applyUserTypeFilters(Builder $builder)
    {
        if (!Tenant::check() || !auth()->check()) {
            return;
        }

        $user = auth()->user();
        $tenant = Tenant::current();
        $tenantUser = $user->getTenantUser($tenant);

        if (!$tenantUser) {
            return;
        }

        switch ($tenantUser->user_type) {
            case TenantUser::TYPE_CLIENT:
                static::applyClientFilters($builder, $tenantUser);
                break;

            case TenantUser::TYPE_EMPLOYEE:
                static::applyEmployeeFilters($builder, $tenantUser);
                break;

            case TenantUser::TYPE_VENDOR:
                static::applyVendorFilters($builder, $tenantUser);
                break;

            case TenantUser::TYPE_PARTNER:
                static::applyPartnerFilters($builder, $tenantUser);
                break;
        }
    }

    /**
     * Apply filters for client users.
     */
    protected static function applyClientFilters(Builder $builder, $tenantUser)
    {
        $table = $builder->getModel()->getTable();

        // Los clientes solo ven sus propios datos
        if (in_array('user_id', $builder->getModel()->getFillable())) {
            $builder->where($table . '.user_id', $tenantUser->user_id);
        }

        // Filtrar por estado si el modelo tiene campo status
        if (in_array('status', $builder->getModel()->getFillable())) {
            $builder->whereIn($table . '.status', ['active', 'pending']);
        }
    }

    /**
     * Apply filters for employee users.
     */
    protected static function applyEmployeeFilters(Builder $builder, $tenantUser)
    {
        $permissions = $tenantUser->permissions ?? [];

        // Los empleados pueden ver datos según sus permisos
        if (!in_array('view_all_data', $permissions)) {
            $table = $builder->getModel()->getTable();

            // Si tiene permisos de su departamento
            if (in_array('view_department_data', $permissions)) {
                $department = $tenantUser->custom_data['department'] ?? null;
                if ($department && in_array('department', $builder->getModel()->getFillable())) {
                    $builder->where($table . '.department', $department);
                }
            }
            // Solo sus propios datos
            elseif (in_array('user_id', $builder->getModel()->getFillable())) {
                $builder->where($table . '.user_id', $tenantUser->user_id);
            }
        }
    }

    /**
     * Apply filters for vendor users.
     */
    protected static function applyVendorFilters(Builder $builder, $tenantUser)
    {
        $table = $builder->getModel()->getTable();

        // Los proveedores solo ven datos relacionados con ellos
        if (in_array('vendor_id', $builder->getModel()->getFillable())) {
            $builder->where($table . '.vendor_id', $tenantUser->user_id);
        } elseif (in_array('user_id', $builder->getModel()->getFillable())) {
            $builder->where($table . '.user_id', $tenantUser->user_id);
        }
    }

    /**
     * Apply filters for partner users.
     */
    protected static function applyPartnerFilters(Builder $builder, $tenantUser)
    {
        $table = $builder->getModel()->getTable();
        $permissions = $tenantUser->permissions ?? [];

        // Los socios pueden tener acceso limitado según acuerdos
        if (in_array('view_partner_data', $permissions)) {
            // Pueden ver datos de otros partners
            if (in_array('partner_id', $builder->getModel()->getFillable())) {
                $builder->whereNotNull($table . '.partner_id');
            }
        } else {
            // Solo sus propios datos
            if (in_array('user_id', $builder->getModel()->getFillable())) {
                $builder->where($table . '.user_id', $tenantUser->user_id);
            }
        }
    }

    /**
     * Scope to bypass user type filters (for admins).
     */
    public function scopeWithoutUserTypeAccess($query)
    {
        return $query->withoutGlobalScope('user_type_access');
    }

    /**
     * Scope to apply specific user type filter.
     */
    public function scopeForUserType($query, string $userType, $userId = null)
    {
        switch ($userType) {
            case TenantUser::TYPE_CLIENT:
                if ($userId && in_array('user_id', $this->getFillable())) {
                    $query->where('user_id', $userId);
                }
                break;

            case TenantUser::TYPE_VENDOR:
                if ($userId) {
                    if (in_array('vendor_id', $this->getFillable())) {
                        $query->where('vendor_id', $userId);
                    } elseif (in_array('user_id', $this->getFillable())) {
                        $query->where('user_id', $userId);
                    }
                }
                break;
        }

        return $query;
    }

    /**
     * Check if current user can view this model instance.
     */
    public function canBeViewedByCurrentUser(): bool
    {
        if (!Tenant::check() || !auth()->check()) {
            return false;
        }

        $user = auth()->user();
        $tenant = Tenant::current();
        $tenantUser = $user->getTenantUser($tenant);

        if (!$tenantUser) {
            return false;
        }

        // Los propietarios y super admins pueden ver todo
        if ($tenantUser->isOwner() || $tenantUser->role === TenantUser::ROLE_SUPER_ADMIN) {
            return true;
        }

        return $this->checkUserTypeAccess($tenantUser);
    }

    /**
     * Check user type specific access to this model.
     */
    protected function checkUserTypeAccess($tenantUser): bool
    {
        switch ($tenantUser->user_type) {
            case TenantUser::TYPE_CLIENT:
                return $this->checkClientAccess($tenantUser);

            case TenantUser::TYPE_EMPLOYEE:
                return $this->checkEmployeeAccess($tenantUser);

            case TenantUser::TYPE_VENDOR:
                return $this->checkVendorAccess($tenantUser);

            case TenantUser::TYPE_PARTNER:
                return $this->checkPartnerAccess($tenantUser);

            default:
                return false;
        }
    }

    /**
     * Check client access to this model.
     */
    protected function checkClientAccess($tenantUser): bool
    {
        // Los clientes solo pueden ver sus propios datos
        return isset($this->user_id) && $this->user_id == $tenantUser->user_id;
    }

    /**
     * Check employee access to this model.
     */
    protected function checkEmployeeAccess($tenantUser): bool
    {
        $permissions = $tenantUser->permissions ?? [];

        // Si puede ver todos los datos
        if (in_array('view_all_data', $permissions)) {
            return true;
        }

        // Si puede ver datos de su departamento
        if (in_array('view_department_data', $permissions)) {
            $department = $tenantUser->custom_data['department'] ?? null;
            return $department && isset($this->department) && $this->department === $department;
        }

        // Solo sus propios datos
        return isset($this->user_id) && $this->user_id == $tenantUser->user_id;
    }

    /**
     * Check vendor access to this model.
     */
    protected function checkVendorAccess($tenantUser): bool
    {
        // Los proveedores solo pueden ver datos relacionados con ellos
        if (isset($this->vendor_id)) {
            return $this->vendor_id == $tenantUser->user_id;
        }

        return isset($this->user_id) && $this->user_id == $tenantUser->user_id;
    }

    /**
     * Check partner access to this model.
     */
    protected function checkPartnerAccess($tenantUser): bool
    {
        $permissions = $tenantUser->permissions ?? [];

        // Si puede ver datos de partners
        if (in_array('view_partner_data', $permissions)) {
            return isset($this->partner_id);
        }

        // Solo sus propios datos
        return isset($this->user_id) && $this->user_id == $tenantUser->user_id;
    }
}