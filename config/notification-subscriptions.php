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
    | Default Rate Limit Duration
    |--------------------------------------------------------------------------
    |
    | The default duration in seconds for rate limiting notifications.
    | This is used as a fallback if your channel enum's rateLimitDuration()
    | method doesn't specify a value. Default is 60 seconds.
    |
    */

    'default_rate_limit_duration' => 60,

];
