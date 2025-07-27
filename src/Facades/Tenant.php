<?php

namespace Kodikas\Multitenant\Facades;

use Illuminate\Support\Facades\Facade;

class Tenant extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return 'tenant';
    }
}
