<?php

namespace Kodikas\Multitenant;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Exceptions\TenantNotResolvedException;

class TenantManager
{
    protected $app;
    protected $currentTenant;
    protected $originalConnection;

    public function __construct($app)
    {
        $this->app = $app;
        $this->originalConnection = config('database.default');
    }

    /**
     * Get the current tenant.
     */
    public function current(): ?Tenant
    {
        return $this->currentTenant;
    }

    /**
     * Set the current tenant.
     */
    public function set(?Tenant $tenant): void
    {
        $this->currentTenant = $tenant;

        if ($tenant) {
            $this->switchToTenant($tenant);
        } else {
            $this->switchToCentral();
        }
    }

    /**
     * Check if we're in a tenant context.
     */
    public function check(): bool
    {
        return $this->currentTenant !== null;
    }

    /**
     * Get current tenant or throw exception.
     */
    public function current(): Tenant
    {
        if (!$this->currentTenant) {
            throw new TenantNotResolvedException('No tenant resolved for current request');
        }

        return $this->currentTenant;
    }

    /**
     * Execute callback within tenant context.
     */
    public function run(Tenant $tenant, callable $callback)
    {
        $originalTenant = $this->currentTenant;

        try {
            $this->set($tenant);
            return $callback();
        } finally {
            $this->set($originalTenant);
        }
    }

    /**
     * Execute callback for each tenant.
     */
    public function each(callable $callback): void
    {
        $tenants = Tenant::active()->get();

        foreach ($tenants as $tenant) {
            $this->run($tenant, function () use ($callback, $tenant) {
                $callback($tenant);
            });
        }
    }

    /**
     * Switch to tenant database.
     */
    protected function switchToTenant(Tenant $tenant): void
    {
        $strategy = config('multitenant.database_strategy');

        switch ($strategy) {
            case 'multiple_databases':
                $this->switchToDifferentDatabase($tenant);
                break;
            case 'multiple_schemas':
                $this->switchToSchema($tenant);
                break;
            case 'single_database':
                $this->setSingleDatabaseTenant($tenant);
                break;
        }

        // Broadcast tenant change
        $this->app['events']->dispatch('tenant.switched', [$tenant]);
    }

    /**
     * Switch to different database for tenant.
     */
    protected function switchToDifferentDatabase(Tenant $tenant): void
    {
        $tenant->configureDatabaseConnection();

        config(['database.default' => $tenant->getConnectionName()]);

        // Clear any cached connections
        DB::purge($tenant->getConnectionName());

        // Test connection
        try {
            DB::connection($tenant->getConnectionName())->getPdo();
        } catch (\Exception $e) {
            throw new \Exception("Could not connect to tenant database: {$e->getMessage()}");
        }
    }

    /**
     * Switch to schema for tenant (PostgreSQL).
     */
    protected function switchToSchema(Tenant $tenant): void
    {
        $schemaName = $tenant->getDatabaseName();

        DB::statement("SET search_path TO {$schemaName}, public");
    }

    /**
     * Set tenant for single database strategy.
     */
    protected function setSingleDatabaseTenant(Tenant $tenant): void
    {
        // This will be handled by the TenantScope globally
        $this->app->instance('current_tenant_id', $tenant->id);
    }

    /**
     * Switch back to central database.
     */
    protected function switchToCentral(): void
    {
        config(['database.default' => $this->originalConnection]);

        $this->app->forgetInstance('current_tenant_id');

        $this->app['events']->dispatch('tenant.cleared');
    }

    /**
     * Forget current tenant.
     */
    public function forget(): void
    {
        $this->set(null);
    }

    /**
     * Find tenant by identifier.
     */
    public function find(string $identifier): ?Tenant
    {
        $cacheKey = config('multitenant.cache.prefix') . "identifier:{$identifier}";

        if (config('multitenant.cache.enabled')) {
            return Cache::remember($cacheKey, config('multitenant.cache.ttl'), function () use ($identifier) {
                return $this->resolveTenant($identifier);
            });
        }

        return $this->resolveTenant($identifier);
    }

    /**
     * Resolve tenant from identifier.
     */
    protected function resolveTenant(string $identifier): ?Tenant
    {
        $method = config('multitenant.identification_method');

        switch ($method) {
            case 'subdomain':
            case 'domain':
                return Tenant::byDomain($identifier)->first();

            case 'path':
                return Tenant::where('slug', $identifier)->first();

            default:
                return Tenant::where('slug', $identifier)->first();
        }
    }

    /**
     * Create new tenant.
     */
    public function create(array $data): Tenant
    {
        DB::beginTransaction();

        try {
            $tenant = Tenant::create($data);

            // Create tenant database if using multiple databases
            if (config('multitenant.database_strategy') === 'multiple_databases') {
                $this->createTenantDatabase($tenant);
            }

            // Run tenant migrations
            $this->runTenantMigrations($tenant);

            DB::commit();

            return $tenant;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create database for tenant.
     */
    protected function createTenantDatabase(Tenant $tenant): void
    {
        $databaseName = $tenant->getDatabaseName();

        DB::statement("CREATE DATABASE IF NOT EXISTS `{$databaseName}`");
    }

    /**
     * Run migrations for tenant.
     */
    protected function runTenantMigrations(Tenant $tenant): void
    {
        $this->run($tenant, function () {
            \Artisan::call('migrate', [
                '--path' => 'database/migrations/tenant',
                '--force' => true,
            ]);
        });
    }

    /**
     * Delete tenant and cleanup.
     */
    public function delete(Tenant $tenant): bool
    {
        DB::beginTransaction();

        try {
            // Drop tenant database if using multiple databases
            if (config('multitenant.database_strategy') === 'multiple_databases') {
                $this->dropTenantDatabase($tenant);
            }

            // Delete tenant
            $tenant->delete();

            // Clear cache
            if (config('multitenant.cache.enabled')) {
                Cache::forget(config('multitenant.cache.prefix') . "identifier:{$tenant->slug}");
                Cache::forget(config('multitenant.cache.prefix') . "identifier:{$tenant->domain}");
                Cache::forget(config('multitenant.cache.prefix') . "identifier:{$tenant->subdomain}");
            }

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Drop tenant database.
     */
    protected function dropTenantDatabase(Tenant $tenant): void
    {
        $databaseName = $tenant->getDatabaseName();

        DB::statement("DROP DATABASE IF EXISTS `{$databaseName}`");
    }
}
