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
        // Configurar base de datos SQLite en memoria
        $app['config']->set('database.default', 'testbench');
        $app['config']->set('database.connections.testbench', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // Configurar cache en array para tests
        $app['config']->set('cache.default', 'array');

        // Configurar session en array para tests
        $app['config']->set('session.driver', 'array');
    }

    /**
     * Configurar la base de datos para tests
     */
    protected function setUpDatabase(): void
    {
        // Ejecutar migraciones si existen
        $this->loadMigrationsFrom(__DIR__ . '/../database/migrations');
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
