<?php

namespace Codinglabs\NotificationSubscriptions\Facades;

use Illuminate\Support\Facades\Facade;
use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsManager;

/**
 * @method static void register(array $notifications)
 * @method static array notifications()
 *
 * @see NotificationSubscriptionsManager
 */
class NotificationSubscriptions extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return NotificationSubscriptionsManager::class;
    }
}
