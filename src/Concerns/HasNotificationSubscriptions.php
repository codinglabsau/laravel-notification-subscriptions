<?php

namespace Codinglabs\NotificationSubscriptions\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Codinglabs\NotificationSubscriptions\Data\NotificationPreferences;
use Codinglabs\NotificationSubscriptions\Models\NotificationSubscription;
use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsManager;

trait HasNotificationSubscriptions
{
    public function notificationSubscriptions(): HasMany
    {
        return $this->hasMany(
            config('notification-subscriptions.subscription_model', NotificationSubscription::class)
        );
    }

    public function getNotificationPreferences(): NotificationPreferences
    {
        $notifications = app(NotificationSubscriptionsManager::class)->notifications();
        $subscriptions = $this->notificationSubscriptions()->get();

        $types = [];
        $values = [];

        foreach ($notifications as $notificationClass) {
            $type = $notificationClass::type();
            $subscription = $subscriptions->firstWhere('type', $type);

            // Types: available channel options (exclude system channels)
            $types[$type] = collect($notificationClass::channels())
                ->reject(fn ($ch) => $ch->isSystemChannel())
                ->filter(fn ($ch) => $ch->isEnabled())
                ->mapWithKeys(fn ($ch) => [$ch->value => $ch->label()])
                ->all();

            // Values: currently enabled channels (from subscription or defaults)
            $values[$type] = $subscription?->channels
                ?? collect($notificationClass::channels())
                    ->filter(fn ($ch) => $ch->defaultOn())
                    ->map(fn ($ch) => $ch->value)
                    ->values()
                    ->all();
        }

        return new NotificationPreferences($types, $values);
    }

    public function updateNotificationPreferences(array $preferences): void
    {
        foreach ($preferences as $type => $channels) {
            $this->notificationSubscriptions()->updateOrCreate(
                ['type' => $type],
                ['channels' => $channels ?? []]
            );
        }
    }
}
