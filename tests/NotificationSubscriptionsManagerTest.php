<?php

use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsManager;
use Codinglabs\NotificationSubscriptions\Facades\NotificationSubscriptions;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestMailOnlyNotification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestPreparesNotification;

test('manager starts with empty notifications array', function () {
    $manager = new NotificationSubscriptionsManager();

    expect($manager->notifications())->toBe([]);
});

test('register adds notifications to the manager', function () {
    $manager = new NotificationSubscriptionsManager();

    $manager->register([TestPreparesNotification::class]);

    expect($manager->notifications())->toBe([TestPreparesNotification::class]);
});

test('register can be called multiple times to add more notifications', function () {
    $manager = new NotificationSubscriptionsManager();

    $manager->register([TestPreparesNotification::class]);
    $manager->register([TestMailOnlyNotification::class]);

    expect($manager->notifications())->toBe([
        TestPreparesNotification::class,
        TestMailOnlyNotification::class,
    ]);
});

test('facade resolves to the manager singleton', function () {
    NotificationSubscriptions::register([TestPreparesNotification::class]);

    $manager = app(NotificationSubscriptionsManager::class);

    expect($manager->notifications())->toContain(TestPreparesNotification::class);
});

test('manager is registered as singleton', function () {
    $manager1 = app(NotificationSubscriptionsManager::class);
    $manager2 = app(NotificationSubscriptionsManager::class);

    expect($manager1)->toBe($manager2);
});
