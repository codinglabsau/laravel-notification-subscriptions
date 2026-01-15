<?php

namespace Codinglabs\NotificationSubscriptions;

class NotificationSubscriptionsManager
{
    protected array $notifications = [];

    public function register(array $notifications): void
    {
        $this->notifications = array_merge($this->notifications, $notifications);
    }

    public function notifications(): array
    {
        return $this->notifications;
    }
}
