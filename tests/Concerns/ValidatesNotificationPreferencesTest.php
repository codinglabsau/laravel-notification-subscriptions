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

// Create a form request that overrides notifications()
class TestLimitedValidationRequest extends FormRequest
{
    use ValidatesNotificationPreferences;

    public function notifications(): array
    {
        return [TestMailOnlyNotification::class];
    }
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

test('prepareForValidation preserves duplicates for notifications without mandatory channels', function () {
    $httpRequest = Request::create('/', 'POST', [
        'test_prepares_notification' => ['mail', 'mail'],
    ]);

    $formRequest = TestValidationRequest::createFrom($httpRequest);
    $formRequest->setContainer(app());

    $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
    $reflection->setAccessible(true);
    $reflection->invoke($formRequest);

    // Duplicates should NOT be stripped — the distinct validation rule handles them
    expect($formRequest->input('test_prepares_notification'))->toBe(['mail', 'mail']);
});

test('prepareForValidation does not modify channels for notifications without mandatory channels', function () {
    $httpRequest = Request::create('/', 'POST', [
        'test_prepares_notification' => ['mail'],
        'test_mail_only' => ['mail'],
    ]);

    $formRequest = TestValidationRequest::createFrom($httpRequest);
    $formRequest->setContainer(app());

    $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
    $reflection->setAccessible(true);
    $reflection->invoke($formRequest);

    // No mandatory channels on these notifications, so input should be unchanged
    expect($formRequest->input('test_prepares_notification'))->toBe(['mail']);
    expect($formRequest->input('test_mail_only'))->toBe(['mail']);
});

test('prepareForValidation handles empty channel array without mandatory channels', function () {
    $httpRequest = Request::create('/', 'POST', [
        'test_prepares_notification' => [],
    ]);

    $formRequest = TestValidationRequest::createFrom($httpRequest);
    $formRequest->setContainer(app());

    $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
    $reflection->setAccessible(true);
    $reflection->invoke($formRequest);

    // No mandatory channels, so should remain empty
    expect($formRequest->input('test_prepares_notification'))->toBe([]);
});

test('default notifications method returns manager registered notifications', function () {
    $request = new TestValidationRequest();

    expect($request->notifications())->toBe([
        TestPreparesNotification::class,
        TestMailOnlyNotification::class,
    ]);
});

test('overridden notifications method limits validation to only those notifications', function () {
    $request = new TestLimitedValidationRequest();

    $rules = $request->rules();

    expect($rules)->toHaveKey('test_mail_only');
    expect($rules)->toHaveKey('test_mail_only.*');
    expect($rules)->not->toHaveKey('test_prepares_notification');
    expect($rules)->not->toHaveKey('test_prepares_notification.*');
});

describe('mandatory channel preservation', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();

        app(NotificationSubscriptionsManager::class)->register([
            TestMandatoryNotification::class,
        ]);
    });

    test('prepareForValidation injects mandatory channels', function () {
        $httpRequest = Request::create('/', 'POST', [
            'test_mandatory_notification' => ['database'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $channels = $formRequest->input('test_mandatory_notification');

        expect($channels)->toContain('mail');
        expect($channels)->toContain('database');
    });

    test('prepareForValidation does not duplicate mandatory channel if already present', function () {
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

    test('mandatory channel is preserved when user submits empty array', function () {
        $httpRequest = Request::create('/', 'POST', [
            'test_mandatory_notification' => [],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $channels = $formRequest->input('test_mandatory_notification');
        expect($channels)->toContain('mail');

        $this->user->updateNotificationPreferences([
            'test_mandatory_notification' => $channels,
        ]);

        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_mandatory_notification')
            ->first();

        expect($subscription->channels)->toContain('mail');
    });

    test('mandatory channel cannot be removed even when explicitly excluded', function () {
        $this->user->notificationSubscriptions()->create([
            'type' => 'test_mandatory_notification',
            'channels' => ['database', 'mail'],
        ]);

        // User tries to update without mail channel
        $httpRequest = Request::create('/', 'POST', [
            'test_mandatory_notification' => ['database'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $this->user->updateNotificationPreferences([
            'test_mandatory_notification' => $formRequest->input('test_mandatory_notification'),
        ]);

        $this->user->refresh();
        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_mandatory_notification')
            ->first();

        expect($subscription->channels)->toContain('mail');
    });

    test('full flow: user preferences correctly reflect mandatory channel preservation', function () {
        $preferences = $this->user->getNotificationPreferences();

        // Mandatory channels should appear in values (defaults)
        expect($preferences->values['test_mandatory_notification'])->toContain('mail');

        // Mandatory channels should appear in types (visible in UI but locked)
        expect($preferences->types['test_mandatory_notification'])->toHaveKey('mail');

        // User submits form without the mandatory channel
        $httpRequest = Request::create('/', 'POST', [
            'test_mandatory_notification' => ['database'],
        ]);

        $formRequest = TestValidationRequest::createFrom($httpRequest);
        $formRequest->setContainer(app());

        $reflection = new ReflectionMethod($formRequest, 'prepareForValidation');
        $reflection->setAccessible(true);
        $reflection->invoke($formRequest);

        $this->user->updateNotificationPreferences([
            'test_mandatory_notification' => $formRequest->input('test_mandatory_notification'),
        ]);

        $subscription = $this->user->notificationSubscriptions()
            ->where('type', 'test_mandatory_notification')
            ->first();

        expect($subscription->channels)->toContain('database');
        expect($subscription->channels)->toContain('mail');

        $this->user->refresh();
        $preferences = $this->user->getNotificationPreferences();

        expect($preferences->values['test_mandatory_notification'])->toContain('database');
        expect($preferences->values['test_mandatory_notification'])->toContain('mail');
    });
});
