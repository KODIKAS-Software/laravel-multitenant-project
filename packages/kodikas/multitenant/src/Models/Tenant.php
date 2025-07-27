<?php

namespace Kodikas\Multitenant\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Kodikas\Multitenant\Events\TenantCreated;
use Kodikas\Multitenant\Events\TenantUpdated;
use Kodikas\Multitenant\Events\TenantDeleted;

class Tenant extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'domain',
        'subdomain',
        'database_name',
        'database_host',
        'database_port',
        'database_username',
        'database_password',
        'status',
        'plan',
        'trial_ends_at',
        'subscription_ends_at',
        'settings',
        'limits',
        'custom_data',
    ];

    protected $casts = [
        'settings' => 'array',
        'limits' => 'array',
        'custom_data' => 'array',
        'trial_ends_at' => 'datetime',
        'subscription_ends_at' => 'datetime',
    ];

    protected $dispatchesEvents = [
        'created' => TenantCreated::class,
        'updated' => TenantUpdated::class,
        'deleted' => TenantDeleted::class,
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    const STATUS_TRIAL = 'trial';

    /**
     * Get the connection name for the tenant.
     */
    public function getConnectionName(): string
    {
        return "tenant_{$this->slug}";
    }

    /**
     * Get the database name for the tenant.
     */
    public function getDatabaseName(): string
    {
        if ($this->database_name) {
            return $this->database_name;
        }

        $prefix = config('multitenant.tenant_database.prefix', 'tenant_');
        $suffix = config('multitenant.tenant_database.suffix', '');

        return $prefix . $this->slug . $suffix;
    }

    /**
     * Check if tenant is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if tenant is on trial.
     */
    public function onTrial(): bool
    {
        return $this->status === self::STATUS_TRIAL &&
               $this->trial_ends_at &&
               $this->trial_ends_at->isFuture();
    }

    /**
     * Check if tenant subscription is active.
     */
    public function subscriptionActive(): bool
    {
        return $this->subscription_ends_at &&
               $this->subscription_ends_at->isFuture();
    }

    /**
     * Check if tenant can perform action based on limits.
     */
    public function canPerform(string $action, int $current = 0): bool
    {
        $limits = $this->getLimits();

        if (!isset($limits[$action])) {
            return true;
        }

        $limit = $limits[$action];

        // -1 means unlimited
        if ($limit === -1) {
            return true;
        }

        return $current < $limit;
    }

    /**
     * Get tenant limits based on plan.
     */
    public function getLimits(): array
    {
        $planLimits = config("multitenant.billing.plans.{$this->plan}.features", []);

        return array_merge($planLimits, $this->limits ?? []);
    }

    /**
     * Get tenant users relationship.
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('multitenant.user_model'),
            'tenant_users'
        )->withPivot(['role', 'status', 'invited_at', 'joined_at'])
          ->withTimestamps();
    }

    /**
     * Get tenant administrators.
     */
    public function administrators(): BelongsToMany
    {
        return $this->users()->wherePivot('role', 'admin');
    }

    /**
     * Get tenant invitations.
     */
    public function invitations(): HasMany
    {
        return $this->hasMany(TenantInvitation::class);
    }

    /**
     * Get pending invitations.
     */
    public function pendingInvitations(): HasMany
    {
        return $this->invitations()->where('status', 'pending');
    }

    /**
     * Scope for active tenants.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for tenants on trial.
     */
    public function scopeOnTrial($query)
    {
        return $query->where('status', self::STATUS_TRIAL)
                    ->where('trial_ends_at', '>', now());
    }

    /**
     * Scope by domain/subdomain.
     */
    public function scopeByDomain($query, string $domain)
    {
        return $query->where('domain', $domain)
                    ->orWhere('subdomain', $domain);
    }

    /**
     * Configure database connection for this tenant.
     */
    public function configureDatabaseConnection(): void
    {
        $connectionName = $this->getConnectionName();

        if (config()->has("database.connections.{$connectionName}")) {
            return;
        }

        $template = config('multitenant.tenant_database.connection_template');
        $template['database'] = $this->getDatabaseName();

        if ($this->database_host) {
            $template['host'] = $this->database_host;
        }

        if ($this->database_port) {
            $template['port'] = $this->database_port;
        }

        if ($this->database_username) {
            $template['username'] = $this->database_username;
        }

        if ($this->database_password) {
            $template['password'] = $this->database_password;
        }

        config()->set("database.connections.{$connectionName}", $template);
    }

    /**
     * Switch to this tenant's database connection.
     */
    public function switchDatabase(): void
    {
        $this->configureDatabaseConnection();

        $strategy = config('multitenant.database_strategy');

        if ($strategy === 'multiple_databases') {
            config()->set('database.default', $this->getConnectionName());
        }
    }

    /**
     * Execute callback within tenant context.
     */
    public function run(callable $callback)
    {
        return app('tenant')->run($this, $callback);
    }
}
