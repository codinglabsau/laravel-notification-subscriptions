<?php

use Illuminate\Support\Facades\Notification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\User;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestChannel;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestSubject;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestDispatchNotification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestMandatoryNotification;

beforeEach(function () {
    Notification::fake();

    $this->user = User::factory()->create();

    TestDispatchNotification::$subscribers = collect([$this->user]);
    TestDispatchNotification::$channels = [TestChannel::DATABASE, TestChannel::MAIL];
    TestDispatchNotification::$subject = null;
});

test('sends notification to all channels by default', function () {
    TestDispatchNotification::sendToSubscribers();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [TestChannel::DATABASE->value, TestChannel::MAIL->value];
    });
});

test('filters out channels if notification subscriptions are set', function () {
    $this->user->notificationSubscriptions()->create([
        'type' => TestDispatchNotification::type(),
        'channels' => [TestChannel::DATABASE->value],
    ]);

    TestDispatchNotification::sendToSubscribers();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [TestChannel::DATABASE->value];
    });
});

test('do not send notifications to channels that are default off', function () {
    TestDispatchNotification::$channels = [TestChannel::DEFAULT_OFF];

    TestDispatchNotification::sendToSubscribers();

    Notification::assertNothingSent();
});

test('do not send notifications to channels that are disabled', function () {
    TestDispatchNotification::$channels = [TestChannel::DISABLED];

    $this->user->notificationSubscriptions()->create([
        'type' => TestDispatchNotification::type(),
        'channels' => [TestChannel::DISABLED->value],
    ]);

    TestDispatchNotification::sendToSubscribers();

    Notification::assertNothingSent();
});

test('rate limited channels are throttled', function () {
    TestDispatchNotification::$channels = [
        TestChannel::DATABASE,
        TestChannel::RATE_LIMITED,
    ];

    TestDispatchNotification::$subject = TestSubject::factory()->create();

    $this->user->notificationSubscriptions()->create([
        'type' => TestDispatchNotification::type(),
        'channels' => [
            TestChannel::DATABASE->value,
            TestChannel::RATE_LIMITED->value,
        ],
    ]);

    TestDispatchNotification::sendToSubscribers();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [
            TestChannel::DATABASE->value,
            TestChannel::RATE_LIMITED->value,
        ];
    });

    // Second dispatch - rate limited channel should be throttled
    TestDispatchNotification::sendToSubscribers();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [TestChannel::DATABASE->value];
    });
});

test('sends to default on channels when no subscription exists', function () {
    TestDispatchNotification::$channels = [
        TestChannel::DATABASE,
        TestChannel::MAIL,
    ];

    TestDispatchNotification::sendToSubscribers();

    Notification::assertSentTo($this->user, function (TestDispatchNotification $notification, array $channels) {
        return $channels === [TestChannel::DATABASE->value, TestChannel::MAIL->value];
    });
});

test('values method returns channel values', function () {
    TestDispatchNotification::$channels = [TestChannel::DATABASE, TestChannel::MAIL];

    expect(TestDispatchNotification::values())->toBe(['database', 'mail']);
});

test('enum has correct labels', function () {
    expect(TestChannel::DATABASE->label())->toBe('In-App');
    expect(TestChannel::MAIL->label())->toBe('Email');
});

test('rate limiting uses channel duration', function () {
    expect(TestChannel::RATE_LIMITED->rateLimitDuration())->toBe(300);
    expect(TestChannel::DATABASE->rateLimitDuration())->toBe(60); // default from config
});

test('beforeSend hook is called before sendToSubscribers', function () {
    TestDispatchNotification::$beforeSendCalled = false;

    TestDispatchNotification::sendToSubscribers();

    expect(TestDispatchNotification::$beforeSendCalled)->toBeTrue();
});

test('databaseType returns notification type', function () {
    $notification = new TestDispatchNotification();

    expect($notification->databaseType($this->user))->toBe('test_notification');
});

test('broadcastType returns notification type', function () {
    $notification = new TestDispatchNotification();

    expect($notification->broadcastType())->toBe('test_notification');
});

test('via method returns channel drivers', function () {
    TestDispatchNotification::$channels = [TestChannel::DATABASE, TestChannel::MAIL];

    $notification = new TestDispatchNotification();

    expect($notification->via($this->user))->toBe(['database', 'mail']);
});

test('rate limiting is skipped when subject returns null', function () {
    TestDispatchNotification::$channels = [TestChannel::RATE_LIMITED];
    TestDispatchNotification::$subject = null; // No subject

    $this->user->notificationSubscriptions()->create([
        'type' => TestDispatchNotification::type(),
        'channels' => [TestChannel::RATE_LIMITED->value],
    ]);

    // Both dispatches should go through since no subject
    TestDispatchNotification::sendToSubscribers();
    TestDispatchNotification::sendToSubscribers();

    Notification::assertSentToTimes($this->user, TestDispatchNotification::class, 2);
});

test('subscribers method returns empty collection by default', function () {
    $notification = new TestDispatchNotification();

    TestDispatchNotification::$subscribers = collect();

    expect($notification->subscribers())->toBeEmpty();
});

test('subject method returns null by default', function () {
    TestDispatchNotification::$subject = null;

    $notification = new TestDispatchNotification();

    expect($notification->subject())->toBeNull();
});

test('isSystemChannel identifies system channels correctly', function () {
    expect(TestChannel::DATABASE->isSystemChannel())->toBeTrue();
    expect(TestChannel::MAIL->isSystemChannel())->toBeFalse();
    expect(TestChannel::SLACK->isSystemChannel())->toBeFalse();
});

test('slack channel defaults to off', function () {
    expect(TestChannel::SLACK->defaultOn())->toBeFalse();
});

test('shouldSend returns true for mandatory channel even when subscription excludes it', function () {
    TestMandatoryNotification::$subscribers = collect([$this->user]);

    // User explicitly opts out of mail
    $this->user->notificationSubscriptions()->create([
        'type' => TestMandatoryNotification::type(),
        'channels' => [TestChannel::DATABASE->value],
    ]);

    TestMandatoryNotification::sendToSubscribers();

    Notification::assertSentTo($this->user, function (TestMandatoryNotification $notification, array $channels) {
        // Mail should still be sent because it's mandatory
        return in_array(TestChannel::MAIL->value, $channels)
            && in_array(TestChannel::DATABASE->value, $channels);
    });
});

test('shouldSend returns false for mandatory channel when channel is disabled', function () {
    TestDispatchNotification::$channels = [TestChannel::DISABLED];
    TestDispatchNotification::$subscribers = collect([$this->user]);

    // Even if we could somehow mark DISABLED as mandatory, it should not send
    $notification = new TestDispatchNotification();

    expect($notification->shouldSend($this->user, TestChannel::DISABLED->driver()))->toBeFalse();
});

test('mandatoryChannels returns empty array by default', function () {
    expect(TestDispatchNotification::mandatoryChannels())->toBe([]);
});
