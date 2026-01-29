<?php

namespace Codinglabs\NotificationSubscriptions\Contracts;

interface SubscribableChannel
{
    /**
     * The Laravel notification channel driver.
     *
     * Examples: 'database', 'mail', OneSignalChannel::class
     */
    public function driver(): string;

    /**
     * Human-readable label for UI.
     *
     * Examples: 'Email', 'Push Notifications', 'In-App'
     */
    public function label(): string;

    /**
     * Whether this channel is currently available/enabled.
     *
     * Use this to conditionally enable channels based on config or feature flags.
     */
    public function isEnabled(): bool;

    /**
     * Whether new users have this channel enabled by default.
     */
    public function defaultOn(): bool;

    /**
     * Whether rate limiting applies to this channel.
     */
    public function hasRateLimiting(): bool;

    /**
     * Rate limit duration in seconds.
     */
    public function rateLimitDuration(): int;
}
