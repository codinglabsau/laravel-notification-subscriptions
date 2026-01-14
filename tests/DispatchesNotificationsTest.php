<?php

use Illuminate\Support\Facades\Notification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\User;
use Codinglabs\NotificationSubscriptions\Enums\ChannelType;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestSubject;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestDispatchNotification;

beforeEach(function () {
    Notification::fake();

    $this->user = User::factory()->create();

    TestDispatchNotification::$subscribers = collect([$this->user]);
    TestDispatchNotification::$channels = [ChannelType::DATABASE, ChannelType::MAIL];
    TestDispatchNotification::$subject = null;
});

test('sends notification to all channels by default', function () {
    TestDispatchNotification::dispatch();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [ChannelType::DATABASE->value, ChannelType::MAIL->value];
    });
});

test('always sends notification to string channels', function () {
    TestDispatchNotification::$channels = ['mail'];
    TestDispatchNotification::dispatch();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === ['mail'];
    });
});

test('filters out channels if notification subscriptions are set', function () {
    $this->user->notificationSubscriptions()->create([
        'type' => TestDispatchNotification::type(),
        'channels' => [ChannelType::DATABASE->toDatabase()],
    ]);

    TestDispatchNotification::dispatch();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [ChannelType::DATABASE->value];
    });
});

test('do not send notifications to channels that are default off', function () {
    config(['notification-subscriptions.channels.broadcast.default_on' => false]);

    TestDispatchNotification::$channels = [
        ChannelType::BROADCAST,
    ];

    TestDispatchNotification::dispatch();

    Notification::assertNothingSent();
});

test('do not send notifications to channels that are configured off', function () {
    config(['notification-subscriptions.channels.mail.enabled' => false]);

    TestDispatchNotification::$channels = [
        ChannelType::MAIL,
    ];

    $this->user->notificationSubscriptions()->create([
        'type' => TestDispatchNotification::type(),
        'channels' => [
            ChannelType::MAIL->toDatabase(),
        ],
    ]);

    TestDispatchNotification::dispatch();

    Notification::assertNothingSent();
});

test('rate limited channels are throttled to configured duration', function () {
    config(['notification-subscriptions.channels.mail.rate_limited' => true]);
    config(['notification-subscriptions.channels.database.rate_limited' => false]);

    TestDispatchNotification::$channels = [
        ChannelType::DATABASE,
        ChannelType::MAIL,
    ];

    TestDispatchNotification::$subject = TestSubject::factory()->create();

    $this->user->notificationSubscriptions()->create([
        'type' => TestDispatchNotification::type(),
        'channels' => [
            ChannelType::DATABASE->toDatabase(),
            ChannelType::MAIL->toDatabase(),
        ],
    ]);

    TestDispatchNotification::dispatch();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [
            ChannelType::DATABASE->value,
            ChannelType::MAIL->value,
        ];
    });

    TestDispatchNotification::dispatch();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [ChannelType::DATABASE->value];
    });
});

test('sends to default on channels when no subscription exists', function () {
    config(['notification-subscriptions.channels.broadcast.default_on' => true]);

    TestDispatchNotification::$channels = [
        ChannelType::DATABASE,
        ChannelType::BROADCAST,
    ];

    TestDispatchNotification::dispatch();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [ChannelType::DATABASE->value, ChannelType::BROADCAST->value];
    });
});

test('channel type values method returns database values', function () {
    TestDispatchNotification::$channels = [ChannelType::DATABASE, ChannelType::MAIL];

    expect(TestDispatchNotification::values())->toBe(['database', 'mail']);
});

test('channel type can read label from config', function () {
    expect(ChannelType::DATABASE->label())->toBe('In-App');
    expect(ChannelType::MAIL->label())->toBe('Email');
    expect(ChannelType::BROADCAST->label())->toBe('Toast');
});
