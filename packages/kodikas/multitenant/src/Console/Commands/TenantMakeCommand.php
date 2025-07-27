<?php

namespace Kodikas\Multitenant\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Kodikas\Multitenant\Models\Tenant;

class TenantMakeCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'tenant:make 
                           {name : The name of the tenant}
                           {--slug= : Custom slug for the tenant}
                           {--domain= : Custom domain for the tenant}
                           {--plan=basic : Subscription plan}
                           {--owner-email= : Email of the tenant owner}';

    /**
     * The console command description.
     */
    protected $description = 'Create a new tenant';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = $this->argument('name');
        $slug = $this->option('slug') ?: Str::slug($name);
        $domain = $this->option('domain');
        $plan = $this->option('plan');
        $ownerEmail = $this->option('owner-email');

        // Verificar si el slug ya existe
        if (Tenant::where('slug', $slug)->exists()) {
            $this->error("Tenant with slug '{$slug}' already exists.");

            return 1;
        }

        $this->info("Creating tenant: {$name}");

        try {
            $tenant = app('tenant')->create([
                'name' => $name,
                'slug' => $slug,
                'domain' => $domain,
                'plan' => $plan,
                'status' => Tenant::STATUS_ACTIVE,
                'settings' => [
                    'app_name' => $name,
                    'locale' => 'es',
                    'timezone' => 'America/Mexico_City',
                ],
            ]);

            $this->info('✅ Tenant created successfully!');
            $this->table(['Field', 'Value'], [
                ['ID', $tenant->id],
                ['Name', $tenant->name],
                ['Slug', $tenant->slug],
                ['Domain', $tenant->domain ?: 'N/A'],
                ['Plan', $tenant->plan],
                ['Status', $tenant->status],
            ]);

            // Crear usuario propietario si se proporciona email
            if ($ownerEmail && $this->confirm("Create owner user for {$ownerEmail}?")) {
                $this->createOwnerUser($tenant, $ownerEmail);
            }

            return 0;
        } catch (\Exception $e) {
            $this->error("Failed to create tenant: {$e->getMessage()}");

            return 1;
        }
    }

    /**
     * Create owner user for tenant.
     */
    protected function createOwnerUser(Tenant $tenant, string $email)
    {
        $userModel = config('multitenant.user_model');

        $user = $userModel::where('email', $email)->first();

        if (! $user) {
            $this->warn("User with email {$email} not found. Please create the user first.");

            return;
        }

        $user->joinTenant($tenant, 'owner', 'super_admin', [
            'view_all_data',
            'manage_users',
            'manage_tenant',
            'billing_access',
        ]);

        $this->info("✅ User {$email} added as owner of tenant {$tenant->name}");
    }
}
