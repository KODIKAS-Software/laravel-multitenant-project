<?php

namespace Kodikas\Multitenant\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Models\TenantUser;

/**
 * Tests unitarios para el modelo Tenant
 * Unit tests for the Tenant model
 */
class TenantTest extends TestCase
{
    /**
     * Test tenant creation.
     * Probar creación de tenant
     *
     * @test
     */
    public function it_can_create_tenant(): void
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
     *
     * @test
     */
    public function it_generates_database_name_correctly(): void
    {
        $tenant = new Tenant(['slug' => 'test-tenant']);

        $this->assertEquals('tenant_test-tenant', $tenant->getDatabaseName());
    }

    /**
     * Test tenant connection name generation.
     * Probar generación de nombre de conexión
     *
     * @test
     */
    public function it_generates_connection_name_correctly(): void
    {
        $tenant = new Tenant(['slug' => 'test-tenant']);

        $this->assertEquals('tenant_test-tenant', $tenant->getConnectionName());
    }

    /**
     * Test tenant status checks.
     * Probar verificaciones de estado del tenant
     *
     * @test
     */
    public function it_checks_tenant_status_correctly(): void
    {
        $activeTenant = new Tenant(['status' => Tenant::STATUS_ACTIVE]);
        $this->assertTrue($activeTenant->isActive());

        $inactiveTenant = new Tenant(['status' => Tenant::STATUS_INACTIVE]);
        $this->assertFalse($inactiveTenant->isActive());

        $trialTenant = new Tenant([
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => now()->addDays(7),
        ]);
        $this->assertTrue($trialTenant->onTrial());

        $expiredTrialTenant = new Tenant([
            'status' => Tenant::STATUS_TRIAL,
            'trial_ends_at' => now()->subDays(1),
        ]);
        $this->assertFalse($expiredTrialTenant->onTrial());
    }

    /**
     * Test tenant subscription checks.
     * Probar verificaciones de suscripción
     *
     * @test
     */
    public function it_checks_subscription_status_correctly(): void
    {
        $activeTenant = new Tenant([
            'subscription_ends_at' => now()->addMonths(6),
        ]);
        $this->assertTrue($activeTenant->subscriptionActive());

        $expiredTenant = new Tenant([
            'subscription_ends_at' => now()->subDays(1),
        ]);
        $this->assertFalse($expiredTenant->subscriptionActive());
    }

    /**
     * Test tenant limits checking.
     * Probar verificación de límites del tenant
     *
     * @test
     */
    public function it_checks_limits_correctly(): void
    {
        $tenant = new Tenant([
            'plan' => 'basic',
            'limits' => ['users' => 10],
        ]);

        $this->assertTrue($tenant->canPerform('users', 5));
        $this->assertFalse($tenant->canPerform('users', 10));
        $this->assertTrue($tenant->canPerform('users', 9));
    }

    /**
     * Test unlimited limits.
     * Probar límites ilimitados
     *
     * @test
     */
    public function it_handles_unlimited_limits(): void
    {
        $tenant = new Tenant([
            'limits' => ['api_calls' => -1],
        ]);

        $this->assertTrue($tenant->canPerform('api_calls', 999999));
    }

    /**
     * Test tenant settings.
     * Probar configuraciones del tenant
     *
     * @test
     */
    public function it_handles_settings_correctly(): void
    {
        $tenant = new Tenant([
            'settings' => [
                'app_name' => 'Mi App',
                'locale' => 'es',
                'timezone' => 'America/Mexico_City',
            ],
        ]);

        $this->assertEquals('Mi App', $tenant->settings['app_name']);
        $this->assertEquals('es', $tenant->settings['locale']);
    }
}
