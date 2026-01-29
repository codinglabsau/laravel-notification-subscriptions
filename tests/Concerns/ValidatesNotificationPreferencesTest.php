<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\User;
use Codinglabs\NotificationSubscriptions\NotificationSubscriptionsManager;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestMailOnlyNotification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestPreparesNotification;
use Codinglabs\NotificationSubscriptions\Tests\Stubs\TestMandatoryNotification;
use Codinglabs\NotificationSubscriptions\Concerns\ValidatesNotificationPreferences;

// Create a test form request
class TestValidationRequest extends FormRequest
{
    use ValidatesNotificationPreferences;
}

beforeEach(function () {
    // Register test notifications
    app(NotificationSubscriptionsManager::class)->register([
        TestPreparesNotification::class,
        TestMailOnlyNotification::class,
    ]);
});

test('rules method generates validation rules for each notification type', function () {
    $request = new TestValidationRequest();

    $rules = $request->rules();

    expect($rules)->toHaveKey('test_prepares_notification');
    expect($rules)->toHaveKey('test_prepares_notification.*');
    expect($rules)->toHaveKey('test_mail_only');
    expect($rules)->toHaveKey('test_mail_only.*');
});

test('rules validates notification type as array', function () {
    $request = new TestValidationRequest();

    $rules = $request->rules();

    expect($rules['test_prepares_notification'])->toContain('array');
});

test('rules validates channels are distinct strings', function () {
    $request = new TestValidationRequest();

    $rules = $request->rules();

    expect($rules['test_prepares_notification.*'])->toContain('distinct');
    expect($rules['test_prepares_notification.*'])->toContain('string');
});

test('validation passes with valid channels', function () {
    $data = [
        'test_prepares_notification' => ['database', 'mail'],
        'test_mail_only' => ['mail'],
    ];

    $request = new TestValidationRequest();
    $rules = $request->rules();

    $validator = Validator::make($data, $rules);

    expect($validator->passes())->toBeTrue();
});

test('validation fails with invalid channel', function () {
    $data = [
        'test_prepares_notification' => ['database', 'invalid_channel'],
    ];

    $request = new TestValidationRequest();
    $rules = $request->rules();

    $validator = Validator::make($data, $rules);

    expect($validator->fails())->toBeTrue();
});

test('validation fails with duplicate channels', function () {
    $data = [
        'test_prepares_notification' => ['database', 'database'],
    ];

    $request = new TestValidationRequest();
    $rules = $request->rules();

    $validator = Validator::make($data, $rules);

    expect($validator->fails())->toBeTrue();
});

test('prepareForValidation injects system channels when notification supports them', function () {
    // Create a real HTTP request with input data
    $httpRequest = Request::create('/', 'POST', [
        'test_prepares_notification' => ['mail'], // No database channel
        'test_mail_only' => ['mail'],
    ]);

    $formRequest = TestValidationRequest::createFrom($httpRequest);
    $formRequest->setContainer(app());

    // Manually call prepareForValidation
    $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
    $reflection->setAccessible(true);
    $reflection->invoke($formRequest);

    // Database channel should be injected for test_prepares_notification
    // (which has DATABASE in its channels and DATABASE.isSystemChannel() returns true)
    expect($formRequest->input('test_prepares_notification'))->toContain('database');
    expect($formRequest->input('test_prepares_notification'))->toContain('mail');

    // test_mail_only doesn't have DATABASE channel, so it shouldn't be injected
    expect($formRequest->input('test_mail_only'))->toBe(['mail']);
});

test('prepareForValidation does not duplicate system channel if already present', function () {
    $httpRequest = Request::create('/', 'POST', [
        'test_prepares_notification' => ['database', 'mail'],
    ]);

    $formRequest = TestValidationRequest::createFrom($httpRequest);
    $formRequest->setContainer(app());

    $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
    $reflection->setAccessible(true);
    $reflection->invoke($formRequest);

    $channels = $formRequest->input('test_prepares_notification');

    // Should not have duplicate database entries
    expect(array_count_values($channels)['database'] ?? 0)->toBe(1);
});

test('prepareForValidation handles empty channel array', function () {
    $httpRequest = Request::create('/', 'POST', [
        'test_prepares_notification' => [],
    ]);

    $formRequest = TestValidationRequest::createFrom($httpRequest);
    $formRequest->setContainer(app());

    $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
    $reflection->setAccessible(true);
    $reflection->invoke($formRequest);

    // Should inject system channel even when array is empty
    expect($formRequest->input('test_prepares_notification'))->toContain('database');
});

describe('system channel preservation', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
    });

    test('system channel is preserved when user submits empty array', function () {
        // User submits empty channels - trying to opt out of everything
        $httpRequest = Request::create('/', 'POST', [
            'test_prepares_notification' => [],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        // Call prepareForValidation
        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        // System channel (database) should be injected
        $channels = $formRequest->input('test_prepares_notification');
        expect($channels)->toContain('database');

        // Now save to database
        $this->user->updateNotificationPreferences([
            'test_prepares_notification' => $channels,
        ]);

        // Verify database has system channel
        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_prepares_notification')
            ->first();

        expect($subscription->channels)->toContain('database');
    });

    test('system channel is preserved when user only selects non-system channels', function () {
        // User only selects mail, not database
        $httpRequest = Request::create('/', 'POST', [
            'test_prepares_notification' => ['mail'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $channels = $formRequest->input('test_prepares_notification');

        // Both mail and database should be present
        expect($channels)->toContain('database');
        expect($channels)->toContain('mail');
    });

    test('notifications without system channels are not affected', function () {
        // TestMailOnlyNotification has only MAIL channel (no system channel)
        $httpRequest = Request::create('/', 'POST', [
            'test_mail_only' => [],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $channels = $formRequest->input('test_mail_only');

        // Should remain empty - no system channel to inject
        expect($channels)->toBe([]);
    });

    test('full flow: user preferences correctly reflect system channel preservation', function () {
        // Step 1: User has no preferences yet
        $preferences = $this->user->getNotificationPreferences();

        // System channels should appear in values (defaults)
        expect($preferences->values['test_prepares_notification'])->toContain('database');

        // System channels should NOT appear in types (UI options)
        expect($preferences->types['test_prepares_notification'])->not->toHaveKey('database');
        expect($preferences->types['test_prepares_notification'])->toHaveKey('mail');

        // Step 2: User submits form with only mail selected
        $httpRequest = Request::create('/', 'POST', [
            'test_prepares_notification' => ['mail'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        // Step 3: Save the processed input
        $this->user->updateNotificationPreferences([
            'test_prepares_notification' => $formRequest->input('test_prepares_notification'),
        ]);

        // Step 4: Verify stored preferences include system channel
        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_prepares_notification')
            ->first();

        expect($subscription->channels)->toContain('database');
        expect($subscription->channels)->toContain('mail');

        // Step 5: Reload preferences and verify
        $this->user->refresh();
        $preferences = $this->user->getNotificationPreferences();

        expect($preferences->values['test_prepares_notification'])->toContain('database');
        expect($preferences->values['test_prepares_notification'])->toContain('mail');
    });

    test('prepareForValidation injects mandatory channels', function () {
        // Register the mandatory notification
        app(NotificationSubscriptionsManager::class)->register([
            TestMandatoryNotification::class,
        ]);

        // User submits without the mandatory mail channel
        $httpRequest = Request::create('/', 'POST', [
            'test_mandatory_notification' => ['database'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $channels = $formRequest->input('test_mandatory_notification');

        // Mail should be injected as it's mandatory
        expect($channels)->toContain('mail');
        expect($channels)->toContain('database');
    });

    test('prepareForValidation does not duplicate mandatory channel if already present', function () {
        app(NotificationSubscriptionsManager::class)->register([
            TestMandatoryNotification::class,
        ]);

        $httpRequest = Request::create('/', 'POST', [
            'test_mandatory_notification' => ['database', 'mail'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $channels = $formRequest->input('test_mandatory_notification');

        expect(array_count_values($channels)['mail'] ?? 0)->toBe(1);
    });

    test('system channel cannot be removed even when explicitly excluded', function () {
        // First, create a subscription with both channels
        $this->user->notificationSubscriptions()->create([
            'type' => 'test_prepares_notification',
            'channels' => ['database', 'mail'],
        ]);

        // User tries to update without database channel
        $httpRequest = Request::create('/', 'POST', [
            'test_prepares_notification' => ['mail'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        // Update with processed input
        $this->user->updateNotificationPreferences([
            'test_prepares_notification' => $formRequest->input('test_prepares_notification'),
        ]);

        // Verify database channel is still there
        $this->user->refresh();
        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_prepares_notification')
            ->first();

        expect($subscription->channels)->toContain('database');
    });
});
