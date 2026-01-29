<?php

namespace Codinglabs\NotificationSubscriptions\Concerns;

use Illuminate\Validation\Rule;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableChannel;
use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsManager;

trait ValidatesNotificationPreferences
{
    public function notifications(): array
    {
        return app(NotificationSubscriptionsManager::class)->notifications();
    }

    public function rules(): array
    {
        return collect($this->notifications())
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
        $notifications = $this->notifications();

        foreach ($notifications as $notification) {
            // Re-inject mandatory channels (users can't opt out of these)
            $mandatoryChannels = collect($notification::mandatoryChannels())
                ->filter(fn (SubscribableChannel $channel) => in_array($channel, $notification::channels()))
                ->map(fn (SubscribableChannel $channel) => $channel->value)
                ->toArray();

            $currentChannels = $this->array($notification::type());

            $this->merge([
                $notification::type() => array_unique(array_merge($currentChannels, $mandatoryChannels)),
            ]);
        }
    }
}
