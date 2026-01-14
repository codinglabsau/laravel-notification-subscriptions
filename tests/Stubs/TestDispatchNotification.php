<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notification;
use Codinglabs\NotificationSubscriptions\Concerns\DispatchesNotifications;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableNotification;

class TestDispatchNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    // hacky properties to allow overrides from test cases
    public static array $channels = [];

    public static ?Model $subject = null;

    public static Collection $subscribers;

    public static function type(): string
    {
        return 'test_notification';
    }

    public function subject(): ?Model
    {
        return static::$subject;
    }

    public static function channels(): array
    {
        return static::$channels;
    }

    public function subscribers(): Collection
    {
        return static::$subscribers;
    }

    public function toArray(object $notifiable): array
    {
        return [
            'message' => 'Test notification',
        ];
    }
}
