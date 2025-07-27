<?php

namespace Kodikas\Multitenant\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kodikas\Multitenant\Models\Tenant;

class SubscriptionUpdated
{
    use Dispatchable, SerializesModels;

    public $tenant;
    public $oldPlan;
    public $newPlan;

    public function __construct(Tenant $tenant, $oldPlan, $newPlan)
    {
        $this->tenant = $tenant;
        $this->oldPlan = $oldPlan;
        $this->newPlan = $newPlan;
    }
}
