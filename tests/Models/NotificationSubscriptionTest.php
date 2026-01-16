<?php

use Codinglabs\NotificationSubscriptions\Tests\Stubs\User;
use Codinglabs\NotificationSubscriptions\Models\NotificationSubscription;

test('uses configured table name', function () {
    $subscription = new NotificationSubscription();

    expect($subscription->getTable())->toBe('notification_subscriptions');
});

test('uses custom table name when configured', function () {
    config(['notification-subscriptions.table' => 'custom_subscriptions']);

    $subscription = new NotificationSubscription();

    expect($subscription->getTable())->toBe('custom_subscriptions');
});

test('channels are cast to json', function () {
    $user = User::factory()->create();

    $subscription = $user->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['database', 'mail'],
    ]);

    $subscription->refresh();

    expect($subscription->channels)->toBeArray();
    expect($subscription->channels)->toBe(['database', 'mail']);
});

test('belongs to user', function () {
    $user = User::factory()->create();

    $subscription = $user->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['database'],
    ]);

    expect($subscription->user)->toBeInstanceOf(User::class);
    expect($subscription->user->id)->toBe($user->id);
});

test('user relationship uses configured model', function () {
    config(['notification-subscriptions.user_model' => User::class]);

    $user = User::factory()->create();

    $subscription = $user->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['database'],
    ]);

    expect($subscription->user)->toBeInstanceOf(User::class);
});

test('can create multiple subscriptions for same user', function () {
    $user = User::factory()->create();

    $user->notificationSubscriptions()->create([
        'type' => 'notification_one',
        'channels' => ['database'],
    ]);

    $user->notificationSubscriptions()->create([
        'type' => 'notification_two',
        'channels' => ['mail'],
    ]);

    expect($user->notificationSubscriptions()->count())->toBe(2);
});

test('enforces unique constraint on user_id and type', function () {
    $user = User::factory()->create();

    $user->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['database'],
    ]);

    expect(fn () => $user->notificationSubscriptions()->create([
        'type' => 'test_notification',
        'channels' => ['mail'],
    ]))->toThrow(Exception::class);
});
