<?php

namespace Codinglabs\NotificationSubscriptions\Tests\Stubs;

use Codinglabs\NotificationSubscriptions\Contracts\SubscribableChannel;

enum TestChannel: string implements SubscribableChannel
{
    case DATABASE = 'database';
    case MAIL = 'mail';
    case SLACK = 'slack';

    // Special test cases for specific behaviors
    case DISABLED = 'disabled';
    case DEFAULT_OFF = 'default_off';
    case RATE_LIMITED = 'rate_limited';

    public function driver(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::DATABASE => 'In-App',
            self::MAIL => 'Email',
            self::SLACK => 'Slack',
            self::DISABLED => 'Disabled Channel',
            self::DEFAULT_OFF => 'Default Off',
            self::RATE_LIMITED => 'Rate Limited',
        };
    }

    public function isEnabled(): bool
    {
        return match ($this) {
            self::DISABLED => false,
            default => true,
        };
    }

    public function defaultOn(): bool
    {
        return match ($this) {
            self::SLACK, self::DEFAULT_OFF => false,
            default => true,
        };
    }

    public function hasRateLimiting(): bool
    {
        return match ($this) {
            self::RATE_LIMITED => true,
            default => false,
        };
    }

    public function rateLimitDuration(): int
    {
        return match ($this) {
            self::RATE_LIMITED => 300,
            default => config('notification-subscriptions.default_rate_limit_duration', 60),
        };
    }
}
