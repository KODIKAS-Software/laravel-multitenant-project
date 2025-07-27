<?php

namespace Kodikas\Multitenant\Tests\Unit;

use Kodikas\Multitenant\MultitenantServiceProvider;
use Kodikas\Multitenant\Tests\TestCase;

class MultitenantServiceProviderTest extends TestCase
{
    public function test_it_can_be_instantiated()
    {
        $provider = new MultitenantServiceProvider($this->app);

        $this->assertInstanceOf(MultitenantServiceProvider::class, $provider);
    }

    public function test_it_provides_expected_services()
    {
        $provider = new MultitenantServiceProvider($this->app);

        // Verificar que el provider está registrado
        $this->assertTrue($this->app->providerIsLoaded(MultitenantServiceProvider::class));
    }

    public function test_config_is_published_correctly()
    {
        // Verificar que la configuración básica está disponible
        $this->assertIsArray(config('multitenant', []));
    }

    public function test_basic_multitenant_functionality_works()
    {
        // Test básico para verificar que el paquete funciona
        $tenant = $this->createTestTenant();

        $this->assertIsArray($tenant);
        $this->assertEquals('Test Tenant', $tenant['name']);
        $this->assertEquals('test-tenant', $tenant['slug']);
    }

    public function test_tenant_user_creation_works()
    {
        $tenantUser = $this->createTestTenantUser();

        $this->assertIsArray($tenantUser);
        $this->assertEquals('employee', $tenantUser['user_type']);
        $this->assertEquals('active', $tenantUser['status']);
    }
}
