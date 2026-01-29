<?php

use Codinglabs\NotificationSubscriptions\Data\NotificationPreferences;

test('DTO can be instantiated with types and values', function () {
    $dto = new NotificationPreferences(
        types: ['order_shipped' => ['mail' => 'Email']],
        values: ['order_shipped' => ['mail']]
    );

    expect($dto->types)->toBe(['order_shipped' => ['mail' => 'Email']]);
    expect($dto->values)->toBe(['order_shipped' => ['mail']]);
});

test('DTO properties are readonly', function () {
    $dto = new NotificationPreferences(
        types: ['order_shipped' => ['mail' => 'Email']],
        values: ['order_shipped' => ['mail']]
    );

    $reflection = new ReflectionClass($dto);

    $typesProperty = $reflection->getProperty('types');
    $valuesProperty = $reflection->getProperty('values');

    expect($typesProperty->isReadOnly())->toBeTrue();
    expect($valuesProperty->isReadOnly())->toBeTrue();
});

test('DTO serializes to JSON correctly for frontend consumption', function () {
    $dto = new NotificationPreferences(
        types: [
            'order_shipped' => ['mail' => 'Email', 'slack' => 'Slack'],
            'new_message' => ['mail' => 'Email'],
        ],
        values: [
            'order_shipped' => ['mail'],
            'new_message' => ['mail', 'slack'],
        ]
    );

    $json = json_encode($dto);
    $decoded = json_decode($json, true);

    expect($decoded['types'])->toBe([
        'order_shipped' => ['mail' => 'Email', 'slack' => 'Slack'],
        'new_message' => ['mail' => 'Email'],
    ]);

    expect($decoded['values'])->toBe([
        'order_shipped' => ['mail'],
        'new_message' => ['mail', 'slack'],
    ]);
});

test('DTO handles empty arrays', function () {
    $dto = new NotificationPreferences(
        types: [],
        values: []
    );

    expect($dto->types)->toBe([]);
    expect($dto->values)->toBe([]);

    $json = json_encode($dto);
    $decoded = json_decode($json, true);

    expect($decoded['types'])->toBe([]);
    expect($decoded['values'])->toBe([]);
});

test('DTO supports mandatory param', function () {
    $dto = new NotificationPreferences(
        types: ['order_shipped' => ['mail' => 'Email']],
        values: ['order_shipped' => ['mail']],
        mandatory: ['order_shipped' => ['mail']]
    );

    expect($dto->mandatory)->toBe(['order_shipped' => ['mail']]);
});

test('DTO mandatory defaults to empty array', function () {
    $dto = new NotificationPreferences(
        types: ['order_shipped' => ['mail' => 'Email']],
        values: ['order_shipped' => ['mail']]
    );

    expect($dto->mandatory)->toBe([]);
});

test('DTO mandatory property is readonly', function () {
    $dto = new NotificationPreferences(
        types: [],
        values: [],
        mandatory: ['order_shipped' => ['mail']]
    );

    $reflection = new ReflectionClass($dto);
    $mandatoryProperty = $reflection->getProperty('mandatory');

    expect($mandatoryProperty->isReadOnly())->toBeTrue();
});

test('DTO serializes mandatory to JSON correctly', function () {
    $dto = new NotificationPreferences(
        types: ['order_shipped' => ['mail' => 'Email']],
        values: ['order_shipped' => ['mail']],
        mandatory: ['order_shipped' => ['mail']]
    );

    $json = json_encode($dto);
    $decoded = json_decode($json, true);

    expect($decoded['mandatory'])->toBe(['order_shipped' => ['mail']]);
});

test('DTO can be used with array spread for Inertia', function () {
    $dto = new NotificationPreferences(
        types: ['order_shipped' => ['mail' => 'Email']],
        values: ['order_shipped' => ['mail']]
    );

    // Simulate how Inertia would receive the data
    $props = ['preferences' => $dto];
    $serialized = json_encode($props);
    $decoded = json_decode($serialized, true);

    expect($decoded['preferences']['types'])->toBe(['order_shipped' => ['mail' => 'Email']]);
    expect($decoded['preferences']['values'])->toBe(['order_shipped' => ['mail']]);
});
