<?php

namespace Codinglabs\NotificationSubscriptions\Traits;

use Illuminate\Validation\Rule;
use Codinglabs\NotificationSubscriptions\Enums\ChannelType;

trait ValidatesNotifications
{
    abstract public function notifications(): array;

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
            // re-inject 'database' channel when it exists on the notification::channels()
            if (
                in_array(ChannelType::DATABASE, $notification::channels())
                && ! in_array(ChannelType::DATABASE->toDatabase(), $this->array($notification::type()))
            ) {
                $this->merge([
                    $notification::type() => [
                        ...$this->array($notification::type()),
                        ChannelType::DATABASE->toDatabase(),
                    ],
                ]);
            }
        }
    }
}
