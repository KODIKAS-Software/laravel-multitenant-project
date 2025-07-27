<?php

namespace Kodikas\Multitenant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Kodikas\Multitenant\Models\Tenant;

class TenantSeedCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant:seed 
                           {tenant? : Tenant ID or slug}
                           {--class= : The class name of the root seeder}
                           {--force : Force the operation to run in production}';

    /**
     * The console command description.
     */
    protected $description = 'Seed the database for tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantIdentifier = $this->argument('tenant');
        $class = $this->option('class');
        $force = $this->option('force');

        if ($tenantIdentifier) {
            $tenant = $this->findTenant($tenantIdentifier);
            if (!$tenant) {
                $this->error("Tenant not found: {$tenantIdentifier}");
                return 1;
            }

            $this->seedTenant($tenant, $class, $force);
        } else {
            $this->seedAllTenants($class, $force);
        }

        return 0;
    }

    /**
     * Find tenant by ID or slug.
     */
    protected function findTenant($identifier): ?Tenant
    {
        if (is_numeric($identifier)) {
            return Tenant::find($identifier);
        }

        return Tenant::where('slug', $identifier)->first();
    }

    /**
     * Seed specific tenant.
     */
    protected function seedTenant(Tenant $tenant, ?string $class, bool $force)
    {
        $this->info("Seeding tenant: {$tenant->name} ({$tenant->slug})");

        app('tenant')->run($tenant, function () use ($class, $force) {
            $options = ['--force' => $force];

            if ($class) {
                $options['--class'] = $class;
            }

            try {
                Artisan::call('db:seed', $options);
                $this->info("✅ Seeding completed for tenant");
            } catch (\Exception $e) {
                $this->error("Seeding failed: {$e->getMessage()}");
            }
        });
    }

    /**
     * Seed all tenants.
     */
    protected function seedAllTenants(?string $class, bool $force)
    {
        $tenants = Tenant::active()->get();

        if ($tenants->isEmpty()) {
            $this->warn("No active tenants found.");
            return;
        }

        $this->info("Found {$tenants->count()} active tenants. Starting seeding...");

        foreach ($tenants as $tenant) {
            try {
                $this->seedTenant($tenant, $class, $force);
            } catch (\Exception $e) {
                $this->error("Failed to seed tenant {$tenant->slug}: {$e->getMessage()}");
            }
        }

        $this->info("✅ All tenant seeding completed!");
    }
}
