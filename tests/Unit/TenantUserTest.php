<?php

namespace Kodikas\Multitenant\Tests\Unit;

use Kodikas\Multitenant\Models\TenantUser;
use Kodikas\Multitenant\Tests\TestCase;

/**
 * Tests unitarios para el modelo TenantUser
 * Unit tests for the TenantUser model
 */
class TenantUserTest extends TestCase
{
    /**
     * Test tenant user creation.
     * Probar creación de usuario tenant
     */
    public function test_it_can_create_tenant_user(): void
    {
        $tenantUser = new TenantUser([
            'tenant_id' => 1,
            'user_id' => 1,
            'user_type' => 'employee',
            'role' => 'employee',
            'status' => 'active',
        ]);

        $this->assertEquals('employee', $tenantUser->user_type);
        $this->assertEquals('employee', $tenantUser->role);
        $this->assertEquals('active', $tenantUser->status);
    }

    /**
     * Test user type checks.
     * Probar verificaciones de tipo de usuario
     */
    public function test_it_checks_user_types_correctly(): void
    {
        $owner = new TenantUser(['user_type' => 'owner']);
        $this->assertEquals('owner', $owner->user_type);

        $employee = new TenantUser(['user_type' => 'employee']);
        $this->assertEquals('employee', $employee->user_type);

        $client = new TenantUser(['user_type' => 'client']);
        $this->assertEquals('client', $client->user_type);
    }

    /**
     * Test admin role checks.
     * Probar verificaciones de rol administrador
     */
    public function test_it_checks_admin_roles_correctly(): void
    {
        $superAdmin = new TenantUser(['role' => 'super_admin']);
        $this->assertEquals('super_admin', $superAdmin->role);

        $admin = new TenantUser(['role' => 'admin']);
        $this->assertEquals('admin', $admin->role);

        $employee = new TenantUser(['role' => 'employee']);
        $this->assertEquals('employee', $employee->role);
    }

    /**
     * Test permission checking.
     * Probar verificación de permisos
     */
    public function test_it_handles_permissions_array(): void
    {
        $employee = new TenantUser([
            'role' => 'employee',
            'permissions' => ['view_reports', 'create_orders'],
        ]);

        $this->assertIsArray($employee->permissions);
        $this->assertContains('view_reports', $employee->permissions);
        $this->assertContains('create_orders', $employee->permissions);
        $this->assertNotContains('manage_users', $employee->permissions);
    }

    /**
     * Test status validation.
     * Probar validación de estado
     */
    public function test_it_validates_status_values(): void
    {
        $activeUser = new TenantUser(['status' => 'active']);
        $this->assertEquals('active', $activeUser->status);

        $inactiveUser = new TenantUser(['status' => 'inactive']);
        $this->assertEquals('inactive', $inactiveUser->status);

        $suspendedUser = new TenantUser(['status' => 'suspended']);
        $this->assertEquals('suspended', $suspendedUser->status);
    }

    /**
     * Test access restrictions.
     * Probar restricciones de acceso
     */
    public function test_it_handles_access_restrictions(): void
    {
        $tenantUser = new TenantUser([
            'status' => 'active',
            'access_restrictions' => [
                'allowed_ips' => ['192.168.1.100', '10.0.0.50'],
                'allowed_times' => ['09:00-17:00'],
            ],
        ]);

        $this->assertIsArray($tenantUser->access_restrictions);
        $this->assertEquals(['192.168.1.100', '10.0.0.50'], $tenantUser->access_restrictions['allowed_ips']);
        $this->assertEquals(['09:00-17:00'], $tenantUser->access_restrictions['allowed_times']);
    }

    /**
     * Test tenant user relationships.
     * Probar relaciones de usuario tenant
     */
    public function test_it_handles_tenant_user_relationships(): void
    {
        $tenantUser = new TenantUser([
            'tenant_id' => 1,
            'user_id' => 2,
            'user_type' => 'employee',
            'role' => 'manager',
        ]);

        $this->assertEquals(1, $tenantUser->tenant_id);
        $this->assertEquals(2, $tenantUser->user_id);
        $this->assertEquals('employee', $tenantUser->user_type);
        $this->assertEquals('manager', $tenantUser->role);
    }

    /**
     * Test custom data handling.
     * Probar manejo de datos personalizados
     */
    public function test_it_handles_custom_data(): void
    {
        $tenantUser = new TenantUser([
            'user_type' => 'employee',
            'permissions' => ['view_dashboard', 'create_reports'],
            'access_restrictions' => ['ip_whitelist' => true],
        ]);

        $this->assertIsArray($tenantUser->permissions);
        $this->assertCount(2, $tenantUser->permissions);

        $this->assertIsArray($tenantUser->access_restrictions);
        $this->assertTrue($tenantUser->access_restrictions['ip_whitelist']);
    }
}
