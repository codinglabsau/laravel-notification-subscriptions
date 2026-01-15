<?php

namespace Codinglabs\NotificationSubscriptions\Database\Factories;

use Codinglabs\NotificationSubscriptions\Models\NotificationSubscription;
use Illuminate\Database\Eloquent\Factories\Factory;

class NotificationSubscriptionFactory extends Factory
{
    protected $model = NotificationSubscription::class;

    public function definition(): array
    {
        return [
            'type' => $this->faker->slug(2),
            'channels' => ['database', 'mail'],
        ];
    }
}
