<?php

namespace Codinglabs\NotificationSubscriptions;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class NotificationSubscriptionsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-notification-subscriptions')
            ->hasConfigFile()
            ->hasMigration('create_notification_subscriptions_table');
    }
}
