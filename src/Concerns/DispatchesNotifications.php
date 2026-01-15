<?php

namespace Codinglabs\NotificationSubscriptions\Concerns;

use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Notification;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableChannel;

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

    public static function sendToSubscribers(...$args): void
    {
        $notification = new static(...$args);

        static::beforeSend($notification);

        Notification::send($notification->subscribers(), $notification);
    }

    public static function values(): array
    {
        return collect(static::channels())
            ->map(fn (SubscribableChannel $channel) => $channel->value)
            ->toArray();
    }

    abstract public static function channels(): array;

    public function via(object $notifiable): array
    {
        return collect(static::channels())
            ->map(fn (SubscribableChannel $channel) => $channel->driver())
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

        // Find the SubscribableChannel enum case by driver name
        $subscribableChannel = collect(static::channels())
            ->first(fn (SubscribableChannel $ch) => $ch->driver() === $channel);

        // Unknown channels pass through (shouldn't happen with proper setup)
        if (! $subscribableChannel) {
            return true;
        }

        // Do not send to channel that is not enabled
        if (! $subscribableChannel->isEnabled()) {
            return false;
        }

        // Check subscription preferences (stored by enum value)
        if ($subscription = $notifiable->notificationSubscriptions()->whereType(static::type())->first()) {
            if (! in_array($subscribableChannel->value, $subscription->channels)) {
                return false;
            }
        } elseif (! $subscribableChannel->defaultOn()) {
            // Do not send to the channel if it is default off and no subscription exists
            return false;
        }

        if ($this->isRateLimited($subscribableChannel, $notifiable)) {
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

    protected function isRateLimited(SubscribableChannel $channel, object $notifiable): bool
    {
        if (! $channel->hasRateLimiting() || ! $this->subject()) {
            return false;
        }

        return ! RateLimiter::attempt(
            key: static::type() . ':' . $channel->value . ':' . $this->subject()->getKey() . ':' . $notifiable->id,
            maxAttempts: 1,
            callback: fn () => true,
            decaySeconds: $channel->rateLimitDuration()
        );
    }
}
