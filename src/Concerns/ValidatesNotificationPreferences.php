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
        foreach ($this->notifications() as $notification) {
            $mandatoryChannels = collect($notification::mandatoryChannels())
                ->filter(fn (SubscribableChannel $channel) => in_array($channel, $notification::channels()))
                ->map(fn (SubscribableChannel $channel) => $channel->value)
                ->toArray();

            if (empty($mandatoryChannels)) {
                continue;
            }

            $this->merge([
                $notification::type() => array_unique(
                    array_merge($this->array($notification::type()), $mandatoryChannels)
                ),
            ]);
        }
    }
}
