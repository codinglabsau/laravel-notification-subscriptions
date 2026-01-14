<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Database Table
    |--------------------------------------------------------------------------
    |
    | The table name used for storing notification subscriptions.
    |
    */

    'table' => 'notification_subscriptions',

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    |
    | The user model class used for the subscription relationship.
    |
    */

    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Subscription Model
    |--------------------------------------------------------------------------
    |
    | The model class used for notification subscriptions. You can extend
    | the default model and specify your own here.
    |
    */

    'subscription_model' => \Codinglabs\NotificationSubscriptions\Models\NotificationSubscription::class,

    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure the available notification channels. Each channel can have:
    | - driver: The Laravel notification channel driver
    | - enabled: Whether the channel is enabled (bool or closure)
    | - default_on: Whether the channel is enabled by default for new users
    | - rate_limited: Whether to apply rate limiting for this channel
    | - label: Human-readable label for the channel
    |
    */

    'channels' => [
        'database' => [
            'driver' => 'database',
            'enabled' => true,
            'default_on' => true,
            'rate_limited' => false,
            'label' => 'In-App',
        ],
        'mail' => [
            'driver' => 'mail',
            'enabled' => true,
            'default_on' => true,
            'rate_limited' => true,
            'label' => 'Email',
        ],
        'broadcast' => [
            'driver' => 'broadcast',
            'enabled' => true,
            'default_on' => true,
            'rate_limited' => true,
            'label' => 'Toast',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Duration
    |--------------------------------------------------------------------------
    |
    | The duration in seconds for rate limiting notifications per
    | subject/channel/type combination. Default is 60 seconds.
    |
    */

    'rate_limit_duration' => 60,

];
