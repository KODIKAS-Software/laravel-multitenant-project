<?php

namespace Kodikas\Multitenant\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kodikas\Multitenant\Models\Tenant;

class TenantUpdated
{
    use Dispatchable, SerializesModels;

    public $tenant;

    public function __construct(Tenant $tenant)
    {
        $this->tenant = $tenant;
    }
}
