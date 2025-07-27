<?php

namespace Kodikas\Multitenant\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodikas\Multitenant\Models\TenantUser;

/**
 * Tests unitarios para el modelo TenantUser
 * Unit tests for the TenantUser model
 */
class TenantUserTest extends TestCase
{
    /**
     * Test tenant user creation.
     * Probar creación de usuario tenant
     *
     * @test
     */
    public function it_can_create_tenant_user(): void
    {
        $tenantUser = new TenantUser([
            'tenant_id' => 1,
            'user_id' => 1,
            'user_type' => TenantUser::TYPE_EMPLOYEE,
            'role' => TenantUser::ROLE_EMPLOYEE,
            'status' => TenantUser::STATUS_ACTIVE,
        ]);

        $this->assertEquals(TenantUser::TYPE_EMPLOYEE, $tenantUser->user_type);
        $this->assertEquals(TenantUser::ROLE_EMPLOYEE, $tenantUser->role);
        $this->assertTrue($tenantUser->isActive());
    }

    /**
     * Test user type checks.
     * Probar verificaciones de tipo de usuario
     *
     * @test
     */
    public function it_checks_user_types_correctly(): void
    {
        $owner = new TenantUser(['user_type' => TenantUser::TYPE_OWNER]);
        $this->assertTrue($owner->isOwner());
        $this->assertFalse($owner->isEmployee());

        $employee = new TenantUser(['user_type' => TenantUser::TYPE_EMPLOYEE]);
        $this->assertTrue($employee->isEmployee());
        $this->assertFalse($employee->isClient());

        $client = new TenantUser(['user_type' => TenantUser::TYPE_CLIENT]);
        $this->assertTrue($client->isClient());
        $this->assertFalse($client->isEmployee());
    }

    /**
     * Test admin role checks.
     * Probar verificaciones de rol administrador
     *
     * @test
     */
    public function it_checks_admin_roles_correctly(): void
    {
        $superAdmin = new TenantUser(['role' => TenantUser::ROLE_SUPER_ADMIN]);
        $this->assertTrue($superAdmin->isAdmin());

        $admin = new TenantUser(['role' => TenantUser::ROLE_ADMIN]);
        $this->assertTrue($admin->isAdmin());

        $employee = new TenantUser(['role' => TenantUser::ROLE_EMPLOYEE]);
        $this->assertFalse($employee->isAdmin());
    }

    /**
     * Test permission checking.
     * Probar verificación de permisos
     *
     * @test
     */
    public function it_checks_permissions_correctly(): void
    {
        $owner = new TenantUser(['user_type' => TenantUser::TYPE_OWNER]);
        $this->assertTrue($owner->hasPermission('any_permission'));

        $superAdmin = new TenantUser(['role' => TenantUser::ROLE_SUPER_ADMIN]);
        $this->assertTrue($superAdmin->hasPermission('any_permission'));

        $employee = new TenantUser([
            'role' => TenantUser::ROLE_EMPLOYEE,
            'permissions' => ['view_reports', 'create_orders'],
        ]);
        $this->assertTrue($employee->hasPermission('view_reports'));
        $this->assertTrue($employee->hasPermission('create_orders'));
        $this->assertFalse($employee->hasPermission('manage_users'));
    }

    /**
     * Test hierarchy levels.
     * Probar niveles de jerarquía
     *
     * @test
     */
    public function it_returns_correct_hierarchy_levels(): void
    {
        $superAdmin = new TenantUser(['role' => TenantUser::ROLE_SUPER_ADMIN]);
        $this->assertEquals(100, $superAdmin->getHierarchyLevel());

        $admin = new TenantUser(['role' => TenantUser::ROLE_ADMIN]);
        $this->assertEquals(90, $admin->getHierarchyLevel());

        $manager = new TenantUser(['role' => TenantUser::ROLE_MANAGER]);
        $this->assertEquals(80, $manager->getHierarchyLevel());

        $employee = new TenantUser(['role' => TenantUser::ROLE_EMPLOYEE]);
        $this->assertEquals(70, $employee->getHierarchyLevel());

        $client = new TenantUser(['role' => TenantUser::ROLE_CLIENT]);
        $this->assertEquals(50, $client->getHierarchyLevel());

        $viewer = new TenantUser(['role' => TenantUser::ROLE_VIEWER]);
        $this->assertEquals(10, $viewer->getHierarchyLevel());
    }

    /**
     * Test access restrictions.
     * Probar restricciones de acceso
     *
     * @test
     */
    public function it_handles_access_restrictions(): void
    {
        // Mock request IP for testing
        $tenantUser = new TenantUser([
            'status' => TenantUser::STATUS_ACTIVE,
            'access_restrictions' => [
                'allowed_ips' => ['192.168.1.100', '10.0.0.50'],
            ],
        ]);

        // Note: En un test real, necesitaríamos mockear la request
        // Note: In a real test, we would need to mock the request
        $this->assertIsArray($tenantUser->access_restrictions);
        $this->assertEquals(['192.168.1.100', '10.0.0.50'], $tenantUser->access_restrictions['allowed_ips']);
    }

    /**
     * Test status checks.
     * Probar verificaciones de estado
     *
     * @test
     */
    public function it_checks_status_correctly(): void
    {
        $active = new TenantUser(['status' => TenantUser::STATUS_ACTIVE]);
        $this->assertTrue($active->isActive());

        $inactive = new TenantUser(['status' => TenantUser::STATUS_INACTIVE]);
        $this->assertFalse($inactive->isActive());

        $suspended = new TenantUser(['status' => TenantUser::STATUS_SUSPENDED]);
        $this->assertFalse($suspended->isActive());

        $pending = new TenantUser(['status' => TenantUser::STATUS_PENDING]);
        $this->assertFalse($pending->isActive());
    }
}
