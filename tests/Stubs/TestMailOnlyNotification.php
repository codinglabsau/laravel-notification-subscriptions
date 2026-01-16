<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Illuminate\Notifications\Notification;
use Codinglabs\NotificationSubscriptions\Concerns\DispatchesNotifications;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableNotification;

class TestMailOnlyNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public static function type(): string
    {
        return 'test_mail_only';
    }

    public static function channels(): array
    {
        return [TestChannel::MAIL];
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => 'Test'];
    }
}
