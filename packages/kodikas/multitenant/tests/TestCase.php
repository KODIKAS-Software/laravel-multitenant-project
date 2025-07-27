<?php

namespace Kodikas\Multitenant\Tests;

use Kodikas\Multitenant\MultitenantServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        // Configurar la base de datos en memoria para tests
        $this->setUpDatabase();
    }

    /**
     * Configurar los service providers necesarios para los tests
     */
    protected function getPackageProviders($app): array
    {
        return [
            MultitenantServiceProvider::class,
        ];
    }

    /**
     * Configurar el entorno de testing
     */
    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');
        config()->set('database.connections.testing', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);

        config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    }

    /**
     * Configurar la base de datos para tests
     */
    protected function setUpDatabase(): void
    {
        // Crear las tablas necesarias para los tests
        $this->artisan('migrate', ['--database' => 'testing']);
    }

    /**
     * Helper para crear un tenant de prueba
     */
    protected function createTestTenant(array $attributes = []): array
    {
        return array_merge([
            'id' => 1,
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
            'domain' => 'test.example.com',
            'database_name' => 'tenant_test',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
    }

    /**
     * Helper para crear un usuario tenant de prueba
     */
    protected function createTestTenantUser(array $attributes = []): array
    {
        return array_merge([
            'id' => 1,
            'tenant_id' => 1,
            'user_id' => 1,
            'user_type' => 'employee',
            'role' => 'employee',
            'status' => 'active',
            'permissions' => [],
            'access_restrictions' => [],
            'created_at' => now(),
            'updated_at' => now(),
        ], $attributes);
    }
}
