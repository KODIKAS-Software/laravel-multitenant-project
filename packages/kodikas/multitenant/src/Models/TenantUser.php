<?php

namespace Kodikas\Multitenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TenantUser extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'user_type',
        'role',
        'status',
        'permissions',
        'invited_by',
        'invited_at',
        'joined_at',
        'last_access_at',
        'access_restrictions',
        'custom_data',
    ];

    protected $casts = [
        'permissions' => 'array',
        'access_restrictions' => 'array',
        'custom_data' => 'array',
        'invited_at' => 'datetime',
        'joined_at' => 'datetime',
        'last_access_at' => 'datetime',
    ];

    // Tipos de usuario
    const TYPE_OWNER = 'owner';           // Propietario del tenant
    const TYPE_ADMIN = 'admin';           // Administrador
    const TYPE_EMPLOYEE = 'employee';     // Empleado
    const TYPE_CLIENT = 'client';         // Cliente
    const TYPE_VENDOR = 'vendor';         // Proveedor
    const TYPE_PARTNER = 'partner';       // Socio
    const TYPE_CONSULTANT = 'consultant'; // Consultor
    const TYPE_GUEST = 'guest';           // Invitado

    // Estados de usuario
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_PENDING = 'pending';
    const STATUS_BLOCKED = 'blocked';

    // Roles predefinidos
    const ROLE_SUPER_ADMIN = 'super_admin';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    const ROLE_EMPLOYEE = 'employee';
    const ROLE_CLIENT = 'client';
    const ROLE_VIEWER = 'viewer';

    /**
     * Get the tenant that owns this relationship.
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the user in this relationship.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(config('multitenant.user_model'), 'user_id');
    }

    /**
     * Get the user who invited this user.
     */
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(config('multitenant.user_model'), 'invited_by');
    }

    /**
     * Check if user is active in this tenant.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if user is owner of tenant.
     */
    public function isOwner(): bool
    {
        return $this->user_type === self::TYPE_OWNER;
    }

    /**
     * Check if user is admin.
     */
    public function isAdmin(): bool
    {
        return in_array($this->role, [self::ROLE_SUPER_ADMIN, self::ROLE_ADMIN]) ||
               $this->user_type === self::TYPE_ADMIN;
    }

    /**
     * Check if user is employee.
     */
    public function isEmployee(): bool
    {
        return $this->user_type === self::TYPE_EMPLOYEE;
    }

    /**
     * Check if user is client.
     */
    public function isClient(): bool
    {
        return $this->user_type === self::TYPE_CLIENT;
    }

    /**
     * Check if user has specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        // Owner y super admin tienen todos los permisos
        if ($this->isOwner() || $this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Verificar permisos específicos
        $permissions = $this->permissions ?? [];
        return in_array($permission, $permissions);
    }

    /**
     * Check if user can access based on tenant restrictions.
     */
    public function canAccess(): bool
    {
        // Verificar estado del usuario
        if (!$this->isActive()) {
            return false;
        }

        // Verificar estado del tenant
        if (!$this->tenant->isActive()) {
            return false;
        }

        // Verificar restricciones de acceso específicas
        return $this->checkAccessRestrictions();
    }

    /**
     * Check access restrictions.
     */
    protected function checkAccessRestrictions(): bool
    {
        $restrictions = $this->access_restrictions ?? [];

        // Verificar restricciones por IP
        if (isset($restrictions['allowed_ips']) && !empty($restrictions['allowed_ips'])) {
            $currentIp = request()->ip();
            if (!in_array($currentIp, $restrictions['allowed_ips'])) {
                return false;
            }
        }

        // Verificar horarios de acceso
        if (isset($restrictions['access_hours'])) {
            $currentHour = now()->hour;
            $start = $restrictions['access_hours']['start'] ?? 0;
            $end = $restrictions['access_hours']['end'] ?? 23;

            if ($currentHour < $start || $currentHour > $end) {
                return false;
            }
        }

        // Verificar días de acceso
        if (isset($restrictions['access_days'])) {
            $currentDay = now()->dayOfWeek; // 0 = domingo, 6 = sábado
            if (!in_array($currentDay, $restrictions['access_days'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Check if user can perform action based on tenant limits and user type.
     */
    public function canPerform(string $action, array $context = []): bool
    {
        // Verificar acceso básico
        if (!$this->canAccess()) {
            return false;
        }

        // Verificar permisos específicos
        if (!$this->hasPermission($action)) {
            return false;
        }

        // Verificar límites del tenant según tipo de usuario
        return $this->checkTenantLimits($action, $context);
    }

    /**
     * Check tenant limits based on user type.
     */
    protected function checkTenantLimits(string $action, array $context = []): bool
    {
        $tenant = $this->tenant;

        // Los propietarios y super admins pueden hacer todo
        if ($this->isOwner() || $this->role === self::ROLE_SUPER_ADMIN) {
            return true;
        }

        // Verificar límites específicos según el tipo de usuario
        switch ($this->user_type) {
            case self::TYPE_CLIENT:
                return $this->checkClientLimits($action, $context);

            case self::TYPE_EMPLOYEE:
                return $this->checkEmployeeLimits($action, $context);

            case self::TYPE_VENDOR:
                return $this->checkVendorLimits($action, $context);

            default:
                return $this->checkGeneralLimits($action, $context);
        }
    }

    /**
     * Check limits for client users.
     */
    protected function checkClientLimits(string $action, array $context = []): bool
    {
        $tenant = $this->tenant;
        $limits = $tenant->getLimits();

        switch ($action) {
            case 'create_order':
                $currentOrders = $context['current_orders'] ?? 0;
                return $tenant->canPerform('client_orders', $currentOrders);

            case 'access_api':
                return $tenant->canPerform('api_calls', $context['current_calls'] ?? 0);

            case 'upload_file':
                $currentStorage = $context['current_storage'] ?? 0;
                return $tenant->canPerform('storage', $currentStorage);

            default:
                return true;
        }
    }

    /**
     * Check limits for employee users.
     */
    protected function checkEmployeeLimits(string $action, array $context = []): bool
    {
        $tenant = $this->tenant;

        switch ($action) {
            case 'manage_clients':
                return $this->hasPermission('manage_clients');

            case 'access_reports':
                return $this->hasPermission('view_reports');

            case 'export_data':
                return $this->hasPermission('export_data') &&
                       $tenant->canPerform('data_exports', $context['current_exports'] ?? 0);

            default:
                return $this->hasPermission($action);
        }
    }

    /**
     * Check limits for vendor users.
     */
    protected function checkVendorLimits(string $action, array $context = []): bool
    {
        $tenant = $this->tenant;

        switch ($action) {
            case 'create_product':
                return $tenant->canPerform('vendor_products', $context['current_products'] ?? 0);

            case 'access_vendor_portal':
                return $tenant->subscriptionActive() || $tenant->onTrial();

            default:
                return $this->hasPermission($action);
        }
    }

    /**
     * Check general limits.
     */
    protected function checkGeneralLimits(string $action, array $context = []): bool
    {
        return $this->hasPermission($action);
    }

    /**
     * Get user hierarchy level (para determinar jerarquía de permisos).
     */
    public function getHierarchyLevel(): int
    {
        switch ($this->role) {
            case self::ROLE_SUPER_ADMIN:
                return 100;
            case self::ROLE_ADMIN:
                return 90;
            case self::ROLE_MANAGER:
                return 80;
            case self::ROLE_EMPLOYEE:
                return 70;
            case self::ROLE_CLIENT:
                return 50;
            case self::ROLE_VIEWER:
                return 10;
            default:
                return 0;
        }
    }

    /**
     * Update last access time.
     */
    public function updateLastAccess(): void
    {
        $this->update(['last_access_at' => now()]);
    }

    /**
     * Scope for active users.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope by user type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('user_type', $type);
    }

    /**
     * Scope by role.
     */
    public function scopeWithRole($query, string $role)
    {
        return $query->where('role', $role);
    }
}
