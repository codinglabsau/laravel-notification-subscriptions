<?php

namespace Codinglabs\NotificationSubscriptions\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Codinglabs\NotificationSubscriptions\NotificationSubscriptions
 */
class NotificationSubscriptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Codinglabs\NotificationSubscriptions\NotificationSubscriptions::class;
    }
}
