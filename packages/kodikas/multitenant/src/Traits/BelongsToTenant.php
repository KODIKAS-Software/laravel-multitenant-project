<?php

namespace Kodikas\Multitenant\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Kodikas\Multitenant\Facades\Tenant;

class TenantScope implements Scope
{
    /**
     * Apply the scope to a given Eloquent query builder.
     */
    public function apply(Builder $builder, Model $model)
    {
        $strategy = config('multitenant.database_strategy');

        if ($strategy === 'single_database' && Tenant::check()) {
            $builder->where($model->getTable().'.tenant_id', Tenant::current()->id);
        }
    }
}

trait BelongsToTenant
{
    /**
     * Boot the belongs to tenant trait.
     */
    protected static function bootBelongsToTenant()
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model) {
            if (Tenant::check() && ! $model->tenant_id) {
                $model->tenant_id = Tenant::current()->id;
            }
        });
    }

    /**
     * Get the tenant that owns this model.
     */
    public function tenant()
    {
        return $this->belongsTo(config('multitenant.tenant_model'));
    }

    /**
     * Scope to exclude tenant filtering (for admin queries).
     */
    public function scopeWithoutTenant($query)
    {
        return $query->withoutGlobalScope(TenantScope::class);
    }

    /**
     * Scope to filter by specific tenant.
     */
    public function scopeForTenant($query, $tenantId)
    {
        return $query->withoutGlobalScope(TenantScope::class)
            ->where('tenant_id', $tenantId);
    }
}
