<?php

use Illuminate\Database\Eloquent\Relations\HasMany;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\User;
use Codinglabs\NotificationSubscriptions\Data\NotificationPreferences;
use Codinglabs\NotificationSubscriptions\Models\NotificationSubscription;
use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsManager;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestMailOnlyNotification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestPreparesNotification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestMandatoryNotification;

test('provides notificationSubscriptions relationship', function () {
    $user = User::factory()->create();

    expect($user->notificationSubscriptions())->toBeInstanceOf(HasMany::class);
});

test('notificationSubscriptions returns empty collection when none exist', function () {
    $user = User::factory()->create();

    expect($user->notificationSubscriptions)->toBeEmpty();
});

test('notificationSubscriptions returns subscriptions for user', function () {
    $user = User::factory()->create();

    $user->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['database', 'mail'],
    ]);

    expect($user->notificationSubscriptions)->toHaveCount(1);
    expect($user->notificationSubscriptions->first())->toBeInstanceOf(NotificationSubscription::class);
});

test('uses configured subscription model', function () {
    config(['notification-subscriptions.subscription_model' => NotificationSubscription::class]);

    $user = User::factory()->create();
    $relationship = $user->notificationSubscriptions();

    expect($relationship->getRelated())->toBeInstanceOf(NotificationSubscription::class);
});

test('can query subscriptions by type', function () {
    $user = User::factory()->create();

    $user->notificationSubscriptions()->create([
        'type' => 'notification_one',
        'channels' => ['database'],
    ]);

    $user->notificationSubscriptions()->create([
        'type' => 'notification_two',
        'channels' => ['mail'],
    ]);

    $subscription = $user->notificationSubscriptions()
        ->whereType('notification_one')
        ->first();

    expect($subscription)->not->toBeNull();
    expect($subscription->type)->toBe('notification_one');
});

test('subscriptions are scoped to user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $user1->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['database'],
    ]);

    $user2->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['mail'],
    ]);

    expect($user1->notificationSubscriptions)->toHaveCount(1);
    expect($user1->notificationSubscriptions->first()->channels)->toBe(['database']);

    expect($user2->notificationSubscriptions)->toHaveCount(1);
    expect($user2->notificationSubscriptions->first()->channels)->toBe(['mail']);
});

describe('notification preferences', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        // Register test notifications
        app(NotificationSubscriptionsManager::class)->register([
            TestPreparesNotification::class,
            TestMailOnlyNotification::class,
        ]);
    });

    test('getNotificationPreferences returns NotificationPreferences DTO', function () {
        $preferences = $this->user->getNotificationPreferences();

        expect($preferences)->toBeInstanceOf(NotificationPreferences::class);
        expect($preferences->types)->toBeArray();
        expect($preferences->values)->toBeArray();
    });

    test('getNotificationPreferences types includes all registered notifications', function () {
        $preferences = $this->user->getNotificationPreferences();

        expect($preferences->types)->toHaveKey('test_prepares_notification');
        expect($preferences->types)->toHaveKey('test_mail_only');
    });

    test('getNotificationPreferences types includes all enabled channels', function () {
        $preferences = $this->user->getNotificationPreferences();

        expect($preferences->types['test_prepares_notification'])->toHaveKey('database');
        expect($preferences->types['test_prepares_notification'])->toHaveKey('mail');
    });

    test('getNotificationPreferences types includes channel labels', function () {
        $preferences = $this->user->getNotificationPreferences();

        expect($preferences->types['test_prepares_notification']['mail'])->toBe('Email');
    });

    test('getNotificationPreferences values returns user subscriptions when they exist', function () {
        $this->user->notificationSubscriptions()->create([
            'type' => 'test_prepares_notification',
            'channels' => ['mail'],
        ]);

        $preferences = $this->user->getNotificationPreferences();

        expect($preferences->values['test_prepares_notification'])->toBe(['mail']);
    });

    test('getNotificationPreferences values returns defaults when no subscription exists', function () {
        $preferences = $this->user->getNotificationPreferences();

        // Both database and mail are default_on: true
        expect($preferences->values['test_prepares_notification'])->toContain('database');
        expect($preferences->values['test_prepares_notification'])->toContain('mail');
    });

    test('getNotificationPreferences handles multiple notification types', function () {
        $this->user->notificationSubscriptions()->create([
            'type' => 'test_prepares_notification',
            'channels' => ['database'],
        ]);

        $preferences = $this->user->getNotificationPreferences();

        // First one has custom subscription
        expect($preferences->values['test_prepares_notification'])->toBe(['database']);
        // Second one uses defaults
        expect($preferences->values['test_mail_only'])->toContain('mail');
    });

    test('updateNotificationPreferences creates new subscriptions', function () {
        $this->user->updateNotificationPreferences([
            'test_prepares_notification' => ['mail'],
            'test_mail_only' => ['mail'],
        ]);

        expect($this->user->notificationSubscriptions()->count())->toBe(2);

        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_prepares_notification')
            ->first();

        expect($subscription->channels)->toBe(['mail']);
    });

    test('updateNotificationPreferences updates existing subscriptions', function () {
        $this->user->notificationSubscriptions()->create([
            'type' => 'test_prepares_notification',
            'channels' => ['database', 'mail'],
        ]);

        $this->user->updateNotificationPreferences([
            'test_prepares_notification' => ['mail'],
        ]);

        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_prepares_notification')
            ->first();

        expect($subscription->channels)->toBe(['mail']);
    });

    test('getNotificationPreferences populates mandatory data', function () {
        app(NotificationSubscriptionsManager::class)->register([
            TestMandatoryNotification::class,
        ]);

        $preferences = $this->user->getNotificationPreferences();

        expect($preferences->mandatory)->toHaveKey('test_mandatory_notification');
        expect($preferences->mandatory['test_mandatory_notification'])->toContain('mail');
    });

    test('getNotificationPreferences mandatory is empty for notifications without mandatory channels', function () {
        $preferences = $this->user->getNotificationPreferences();

        // TestPreparesNotification and TestMailOnlyNotification have no mandatory channels
        expect($preferences->mandatory)->not->toHaveKey('test_prepares_notification');
        expect($preferences->mandatory)->not->toHaveKey('test_mail_only');
    });

    test('updateNotificationPreferences handles empty channels array', function () {
        $this->user->updateNotificationPreferences([
            'test_prepares_notification' => [],
        ]);

        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_prepares_notification')
            ->first();

        expect($subscription->channels)->toBe([]);
    });
});
