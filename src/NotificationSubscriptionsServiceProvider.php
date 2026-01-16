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

    public function packageRegistered(): void
    {
        $this->app->singleton(NotificationSubscriptionsManager::class);
    }

    public function packageBooted(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../stubs/NotificationSubscriptionsServiceProvider.php.stub' => app_path('Providers/NotificationSubscriptionsServiceProvider.php'),
            ], 'laravel-notification-subscriptions-provider');
        }
    }
}
