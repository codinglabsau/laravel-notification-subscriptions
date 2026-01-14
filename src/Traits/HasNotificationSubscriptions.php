<?php

namespace Codinglabs\NotificationSubscriptions\Traits;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Codinglabs\NotificationSubscriptions\Models\NotificationSubscription;

trait HasNotificationSubscriptions
{
    public function notificationSubscriptions(): HasMany
    {
        return $this->hasMany(
            config('notification-subscriptions.subscription_model', NotificationSubscription::class)
        );
    }
}
