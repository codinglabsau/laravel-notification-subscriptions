<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Illuminate\Notifications\Notification;
use Codinglabs\NotificationSubscriptions\Concerns\DispatchesNotifications;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableNotification;

class TestPreparesNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public static function type(): string
    {
        return 'test_prepares_notification';
    }

    public static function channels(): array
    {
        return [TestChannel::DATABASE, TestChannel::MAIL];
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => 'Test'];
    }
}
