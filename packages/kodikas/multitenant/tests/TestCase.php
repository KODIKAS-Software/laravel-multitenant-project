<?php

namespace Kodikas\Multitenant\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Kodikas\Multitenant\MultitenantServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Kodikas\\Multitenant\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
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
        // Database configuration
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        // App configuration
        $app['config']->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
        $app['config']->set('app.env', 'testing');

        // Cache configuration
        $app['config']->set('cache.default', 'array');
        $app['config']->set('session.driver', 'array');
        $app['config']->set('queue.default', 'sync');

        // Multitenant configuration
        $app['config']->set('multitenant.database.strategy', 'single');
        $app['config']->set('multitenant.database.prefix', 'tenant_');
        $app['config']->set('multitenant.identification.methods', ['subdomain']);
        $app['config']->set('multitenant.identification.subdomain.domain', 'example.com');

        // Billing plans configuration
        $app['config']->set('multitenant.billing.plans', [
            'basic' => [
                'name' => 'Básico',
                'limits' => [
                    'users' => 5,
                    'storage' => 1024,
                    'api_calls' => 1000,
                ],
            ],
            'pro' => [
                'name' => 'Profesional',
                'limits' => [
                    'users' => 50,
                    'storage' => 10240,
                    'api_calls' => 10000,
                ],
            ],
            'enterprise' => [
                'name' => 'Empresarial',
                'limits' => [
                    'users' => -1, // unlimited
                    'storage' => -1,
                    'api_calls' => -1,
                ],
            ],
        ]);
    }

    /**
     * Resolver las dependencias de la aplicación
     */
    protected function resolveApplicationConfiguration($app)
    {
        parent::resolveApplicationConfiguration($app);

        $app['config']->set('multitenant', [
            'database' => [
                'strategy' => 'single',
                'prefix' => 'tenant_',
                'connection' => 'testing',
            ],
            'identification' => [
                'methods' => ['subdomain'],
                'subdomain' => [
                    'domain' => 'example.com',
                ],
            ],
            'billing' => [
                'plans' => [
                    'basic' => [
                        'name' => 'Básico',
                        'limits' => [
                            'users' => 5,
                            'storage' => 1024,
                            'api_calls' => 1000,
                        ],
                    ],
                    'pro' => [
                        'name' => 'Profesional',
                        'limits' => [
                            'users' => 50,
                            'storage' => 10240,
                            'api_calls' => 10000,
                        ],
                    ],
                    'enterprise' => [
                        'name' => 'Empresarial',
                        'limits' => [
                            'users' => -1,
                            'storage' => -1,
                            'api_calls' => -1,
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * Define database migrations.
     */
    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
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
            'plan' => 'basic',
            'settings' => [],
            'custom_data' => [],
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
