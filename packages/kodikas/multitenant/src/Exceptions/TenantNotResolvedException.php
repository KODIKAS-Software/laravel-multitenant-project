<?php

namespace Kodikas\Multitenant\Exceptions;

use Exception;

class TenantNotResolvedException extends Exception
{
    protected $message = 'No tenant could be resolved for the current request.';
}
