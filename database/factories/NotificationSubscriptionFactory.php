<?php

namespace Codinglabs\NotificationSubscriptions\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Codinglabs\NotificationSubscriptions\Models\NotificationSubscription;

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
