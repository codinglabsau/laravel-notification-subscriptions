<?php

namespace Codinglabs\NotificationSubscriptions\Traits;

use Illuminate\Support\Collection;
use Codinglabs\NotificationSubscriptions\Enums\ChannelType;

trait PreparesNotificationsForEditing
{
    protected function prepareTypes(array $types): array
    {
        return collect($types)
            ->mapWithKeys(fn (string $type) => [
                $type::type() => $this->channelOptions($type),
            ])
            ->toArray();
    }

    protected function channelOptions(string $notification): array
    {
        return collect($notification::channels())
            ->filter(function (ChannelType $channel) {
                // drop the database channel, if set, it will be re-injected on update
                if ($channel === ChannelType::DATABASE) {
                    return false;
                }

                return $channel->isEnabled();
            })
            ->mapWithKeys(fn (ChannelType $channel) => [
                $channel->toDatabase() => $channel->label(),
            ])
            ->sort()
            ->toArray();
    }

    protected function prepareValues(array $types, object $user): array
    {
        $notificationSubscriptions = $user
            ->notificationSubscriptions()
            ->get();

        return collect($types)
            ->mapWithKeys(fn (string $type) => [
                $type::type() => $this->extractChannelsOrDefaults($notificationSubscriptions, $type),
            ])
            ->toArray();
    }

    protected function extractChannelsOrDefaults(Collection $notificationSubscriptions, string $type): array
    {
        return $notificationSubscriptions->where('type', $type::type())->first()->channels
            ?? collect($type::channels())
                ->filter(fn (ChannelType $channel) => $channel->defaultOn())
                ->map(fn (ChannelType $channel) => $channel->toDatabase())
                ->values()
                ->toArray();
    }
}
