<?php

namespace Codinglabs\NotificationSubscriptions\Concerns;

use Illuminate\Validation\Rule;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableChannel;
use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsManager;

trait ValidatesNotificationPreferences
{
    public function rules(): array
    {
        $notifications = app(NotificationSubscriptionsManager::class)->notifications();

        return collect($notifications)
            ->mapWithKeys(fn (string $notification) => [
                $notification::type() => [
                    'array',
                ],
                $notification::type() . '.*' => [
                    'distinct',
                    'string',
                    Rule::in($notification::values()),
                ],
            ])
            ->toArray();
    }

    protected function prepareForValidation(): void
    {
        $notifications = app(NotificationSubscriptionsManager::class)->notifications();

        foreach ($notifications as $notification) {
            // Re-inject system channels (users can't opt out of these)
            $systemChannels = collect($notification::channels())
                ->filter(fn (SubscribableChannel $channel) => $channel->isSystemChannel())
                ->map(fn (SubscribableChannel $channel) => $channel->value)
                ->toArray();

            // Re-inject mandatory channels (users can't opt out of these either)
            $mandatoryChannels = collect($notification::mandatoryChannels())
                ->filter(fn (SubscribableChannel $channel) => in_array($channel, $notification::channels()))
                ->map(fn (SubscribableChannel $channel) => $channel->value)
                ->toArray();

            $currentChannels = $this->array($notification::type());

            // Merge system and mandatory channels back into the request
            $this->merge([
                $notification::type() => array_unique(array_merge($currentChannels, $systemChannels, $mandatoryChannels)),
            ]);
        }
    }
}
