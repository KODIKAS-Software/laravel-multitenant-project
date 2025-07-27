<?php

namespace Kodikas\Multitenant\Tests\Unit;

use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Tests\TestCase;

/**
 * Tests unitarios para el modelo Tenant
 * Unit tests for the Tenant model
 */
class TenantTest extends TestCase
{
    /**
     * Test tenant creation.
     * Probar creación de tenant
     */
    public function test_it_can_create_tenant(): void
    {
        $tenant = new Tenant([
            'name' => 'Test Company',
            'slug' => 'test-company',
            'status' => Tenant::STATUS_ACTIVE,
            'plan' => 'basic',
        ]);

        $this->assertEquals('Test Company', $tenant->name);
        $this->assertEquals('test-company', $tenant->slug);
        $this->assertTrue($tenant->isActive());
    }

    /**
     * Test tenant database name generation.
     * Probar generación de nombre de base de datos
     */
    public function test_it_generates_database_name_correctly(): void
    {
        $tenant = new Tenant(['slug' => 'test-tenant']);

        $this->assertEquals('tenant_test-tenant', $tenant->getDatabaseName());
    }

    /**
     * Test tenant connection name generation.
     * Probar generación de nombre de conexión
     */
    public function test_it_generates_connection_name_correctly(): void
    {
        $tenant = new Tenant(['slug' => 'test-tenant']);

        $this->assertEquals('tenant_test-tenant', $tenant->getConnectionName());
    }

    /**
     * Test tenant status methods.
     * Probar métodos de estado del tenant
     */
    public function test_it_checks_tenant_status_correctly(): void
    {
        $tenant = new Tenant(['status' => Tenant::STATUS_ACTIVE]);
        $this->assertTrue($tenant->isActive());

        $tenant->status = Tenant::STATUS_INACTIVE;
        $this->assertFalse($tenant->isActive());
    }

    /**
     * Test tenant subscription status.
     * Probar estado de suscripción del tenant
     */
    public function test_it_checks_subscription_status_correctly(): void
    {
        $tenant = new Tenant([
            'subscription_ends_at' => now()->addDays(30),
        ]);

        $this->assertTrue($tenant->subscriptionActive());

        $tenant->subscription_ends_at = now()->subDays(1);
        $this->assertFalse($tenant->subscriptionActive());
    }

    /**
     * Test tenant limits.
     * Probar límites del tenant
     */
    public function test_it_checks_limits_correctly(): void
    {
        $tenant = new Tenant([
            'plan' => 'basic',
        ]);

        // Test with basic plan limits (5 users)
        $this->assertTrue($tenant->canPerform('users', 4));
        $this->assertFalse($tenant->canPerform('users', 5));
    }

    /**
     * Test tenant unlimited limits.
     * Probar límites ilimitados del tenant
     */
    public function test_it_handles_unlimited_limits(): void
    {
        $tenant = new Tenant([
            'plan' => 'enterprise',
        ]);

        // Enterprise plan has unlimited users (-1)
        $this->assertTrue($tenant->canPerform('users', 1000));
        $this->assertTrue($tenant->canPerform('users', 9999));
    }

    /**
     * Test tenant trial status.
     * Probar estado de prueba del tenant
     */
    public function test_it_checks_trial_status_correctly(): void
    {
        $tenant = new Tenant([
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(7),
        ]);

        $this->assertTrue($tenant->onTrial());

        $tenant->trial_ends_at = now()->subDays(1);
        $this->assertFalse($tenant->onTrial());
    }

    /**
     * Test tenant limits retrieval.
     * Probar obtención de límites del tenant
     */
    public function test_it_retrieves_limits_from_plan(): void
    {
        $tenant = new Tenant([
            'plan' => 'basic',
        ]);

        $limits = $tenant->getLimits();

        $this->assertIsArray($limits);
        $this->assertEquals(5, $limits['users']);
        $this->assertEquals(1024, $limits['storage']);
        $this->assertEquals(1000, $limits['api_calls']);
    }
}
