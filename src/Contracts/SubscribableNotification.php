<?php

namespace Codinglabs\NotificationSubscriptions\Contracts;

use Illuminate\Database\Eloquent\Model;

interface SubscribableNotification
{
    /**
     * Unique identifier for this notification type.
     */
    public static function type(): string;

    /**
     * The channels this notification supports.
     *
     * @return SubscribableChannel[]
     */
    public static function channels(): array;

    /**
     * Get channel values as strings for database storage.
     *
     * @return string[]
     */
    public static function values(): array;

    /**
     * The model that triggered this notification (for rate limiting).
     */
    public function subject(): ?Model;

    /**
     * Send this notification to all subscribers, respecting their preferences.
     */
    public static function sendToSubscribers();

    /**
     * Get the users who should receive this notification.
     */
    public function subscribers();
}
