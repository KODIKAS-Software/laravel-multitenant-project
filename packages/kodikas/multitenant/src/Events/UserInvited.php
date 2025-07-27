<?php

namespace Kodikas\Multitenant\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Kodikas\Multitenant\Models\TenantInvitation;

class UserInvited
{
    use Dispatchable, SerializesModels;

    public $invitation;

    public function __construct(TenantInvitation $invitation)
    {
        $this->invitation = $invitation;
    }
}
