<?php

namespace Codinglabs\NotificationSubscriptions\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Codinglabs\NotificationSubscriptions\Enums\ChannelType;

trait DispatchesNotifications
{
    public function __construct()
    {
        // default constructor to allow instantiation without parameters
    }

    public static function beforeSend($notification): void
    {
        // do nothing...
    }

    public static function dispatch(): void
    {
        $notification = new self(...func_get_args());

        static::beforeSend($notification);

        Notification::send($notification->subscribers(), $notification);
    }

    public static function values(): array
    {
        return collect(static::channels())
            ->map(fn (ChannelType|string $channel) => $channel instanceof ChannelType ? $channel->toDatabase() : $channel)
            ->toArray();
    }

    abstract public static function channels(): array;

    public function via(object $notifiable): array
    {
        return collect(static::channels())
            ->map(fn (ChannelType|string $channel) => $channel instanceof ChannelType ? $channel->value : $channel)
            ->toArray();
    }

    public function subscribers(): Collection
    {
        return collect();
    }

    public function subject(): ?Model
    {
        return null;
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (method_exists(get_parent_class($this), 'shouldSend')) {
            if (! parent::shouldSend($notifiable, $channel)) {
                return false;
            }
        }

        $channelType = ChannelType::tryFrom($channel);

        if (is_null($channelType)) {
            return true;
        }

        // do not send to channel that is not enabled
        if (! $channelType->isEnabled()) {
            return false;
        }

        if ($subscription = $notifiable->notificationSubscriptions()->whereType(static::type())->first()) {
            // do not send if the user has subscribed to the notification type, but not the channel
            if (! in_array($channelType->toDatabase(), $subscription->channels)) {
                return false;
            }
        }

        // do not send to the channel if it is default off
        if (! $channelType->defaultOn() && ! $subscription) {
            return false;
        }

        if ($this->isRateLimited($channelType, $notifiable)) {
            return false;
        }

        return true;
    }

    public function databaseType(object $notifiable): string
    {
        return static::type();
    }

    public function broadcastType(): string
    {
        return static::type();
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable)->toArray());
    }

    protected function isRateLimited(ChannelType $channelType, object $notifiable): bool
    {
        if (! $channelType->hasRateLimiting() || ! $this->subject()) {
            return false;
        }

        $duration = config('notification-subscriptions.rate_limit_duration', 60);

        return ! RateLimiter::attempt(
            key: static::type() . ':' . $channelType->toDatabase() . ':' . $this->subject()->getKey() . ':' . $notifiable->id,
            maxAttempts: 1,
            callback: fn () => true,
            decaySeconds: $duration
        );
    }
}
