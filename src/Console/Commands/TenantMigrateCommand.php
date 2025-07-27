E<?php

namespace Kodikas\Multitenant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Kodikas\Multitenant\Models\Tenant;

class TenantMigrateCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant:migrate 
                           {tenant? : Tenant ID or slug}
                           {--fresh : Drop all tables and re-run all migrations}
                           {--seed : Seed the database after running migrations}
                           {--force : Force the operation to run in production}';

    /**
     * The console command description.
     */
    protected $description = 'Run migrations for tenants';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantIdentifier = $this->argument('tenant');
        $fresh = $this->option('fresh');
        $seed = $this->option('seed');
        $force = $this->option('force');

        if ($tenantIdentifier) {
            $tenant = $this->findTenant($tenantIdentifier);
            if (!$tenant) {
                $this->error("Tenant not found: {$tenantIdentifier}");
                return 1;
            }

            $this->migrateTenant($tenant, $fresh, $seed, $force);
        } else {
            $this->migrateAllTenants($fresh, $seed, $force);
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
     * Migrate specific tenant.
     */
    protected function migrateTenant(Tenant $tenant, bool $fresh, bool $seed, bool $force)
    {
        $this->info("Running migrations for tenant: {$tenant->name} ({$tenant->slug})");

        app('tenant')->run($tenant, function () use ($fresh, $seed, $force) {
            $command = $fresh ? 'migrate:fresh' : 'migrate';

            $options = ['--force' => $force];

            if ($fresh && $seed) {
                $options['--seed'] = true;
            }

            try {
                Artisan::call($command, $options);
                $this->info("✅ Migrations completed for tenant");

                if (!$fresh && $seed) {
                    Artisan::call('db:seed', ['--force' => $force]);
                    $this->info("✅ Seeding completed for tenant");
                }
            } catch (\Exception $e) {
                $this->error("Migration failed: {$e->getMessage()}");
            }
        });
    }

    /**
     * Migrate all tenants.
     */
    protected function migrateAllTenants(bool $fresh, bool $seed, bool $force)
    {
        $tenants = Tenant::all();

        if ($tenants->isEmpty()) {
            $this->warn("No tenants found.");
            return;
        }

        $this->info("Found {$tenants->count()} tenants. Starting migrations...");

        $progressBar = $this->output->createProgressBar($tenants->count());
        $progressBar->start();

        foreach ($tenants as $tenant) {
            try {
                $this->migrateTenant($tenant, $fresh, $seed, $force);
                $progressBar->advance();
            } catch (\Exception $e) {
                $this->error("\nFailed to migrate tenant {$tenant->slug}: {$e->getMessage()}");
            }
        }

        $progressBar->finish();
        $this->info("\n✅ All tenant migrations completed!");
    }
}
