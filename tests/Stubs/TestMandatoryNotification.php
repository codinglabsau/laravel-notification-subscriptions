<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Illuminate\Support\Collection;
use Illuminate\Notifications\Notification;
use Codinglabs\NotificationSubscriptions\Concerns\DispatchesNotifications;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableNotification;

class TestMandatoryNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public static Collection $subscribers;

    public static function type(): string
    {
        return 'test_mandatory_notification';
    }

    public static function channels(): array
    {
        return [TestChannel::DATABASE, TestChannel::MAIL, TestChannel::SLACK];
    }

    /**
     * Mail is mandatory — users cannot unsubscribe from it.
     */
    public static function mandatoryChannels(): array
    {
        return [TestChannel::MAIL];
    }

    public function subscribers(): Collection
    {
        return static::$subscribers;
    }

    public function toArray(object $notifiable): array
    {
        return ['message' => 'Test mandatory'];
    }
}
