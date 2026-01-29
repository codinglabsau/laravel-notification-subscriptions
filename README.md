# Laravel Notification Subscriptions

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codinglabsau/laravel-notification-subscriptions.svg?style=flat-square)](https://packagist.org/packages/codinglabsau/laravel-notification-subscriptions)
[![Test](https://github.com/codinglabsau/laravel-notification-subscriptions/actions/workflows/run-tests.yml/badge.svg)](https://github.com/codinglabsau/laravel-notification-subscriptions/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/codinglabsau/laravel-notification-subscriptions.svg?style=flat-square)](https://packagist.org/packages/codinglabsau/laravel-notification-subscriptions)

A Laravel package for managing user notification preferences across multiple channels. Let your users control how they receive notifications (email, in-app, push, Slack) while you maintain sensible defaults and rate limiting.

## Features

- **Channel-based subscriptions** - Users can enable/disable notifications per channel
- **Custom channels** - Define your own channel enum with any channels you need (mail, database, Slack, Pusher, OneSignal, etc.)
- **Per-notification control** - Configure which channels each notification type supports
- **Smart defaults** - New users get sensible defaults; preferences are only stored when changed
- **Rate limiting** - Prevent notification spam with configurable per-channel rate limits
- **System channels** - Mark channels that users can't opt out of (like in-app notifications)
- **Simple API** - `$user->getNotificationPreferences()` and `$user->updateNotificationPreferences()` for settings UIs

## Installation

### 1. Install via Composer

```bash
composer require codinglabsau/laravel-notification-subscriptions
```

### 2. Publish and run migrations

```bash
php artisan vendor:publish --tag="laravel-notification-subscriptions-migrations"
php artisan migrate
```

### 3. Publish configuration (optional)

```bash
php artisan vendor:publish --tag="laravel-notification-subscriptions-config"
```

### 4. Create your channel enum

Create an enum that implements `SubscribableChannel` to define your notification channels:

```php
// app/Enums/NotificationChannel.php
namespace App\Enums;

use Codinglabs\NotificationSubscriptions\Contracts\SubscribableChannel;

enum NotificationChannel: string implements SubscribableChannel
{
    case DATABASE = 'database';
    case MAIL = 'mail';
    case SLACK = 'slack';

    public function driver(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::DATABASE => 'In-App',
            self::MAIL => 'Email',
            self::SLACK => 'Slack',
        };
    }

    public function isEnabled(): bool
    {
        return true;
    }

    public function defaultOn(): bool
    {
        return match ($this) {
            self::SLACK => false,
            default => true,
        };
    }

    public function hasRateLimiting(): bool
    {
        return match ($this) {
            self::DATABASE => false,
            default => true,
        };
    }

    public function rateLimitDuration(): int
    {
        return match ($this) {
            self::MAIL => 300, // 5 minutes
            default => 60,
        };
    }

    public function isSystemChannel(): bool
    {
        return $this === self::DATABASE;
    }
}
```

### 5. Add trait to your User model

```php
use Codinglabs\NotificationSubscriptions\Concerns\HasNotificationSubscriptions;

class User extends Authenticatable
{
    use HasNotificationSubscriptions;

    // ...
}
```

### 6. Publish and configure the service provider

```bash
php artisan vendor:publish --tag="laravel-notification-subscriptions-provider"
```

Then register your subscribable notifications in `app/Providers/NotificationSubscriptionsServiceProvider.php`:

```php
use Codinglabs\NotificationSubscriptions\Facades\NotificationSubscriptions;

public function boot(): void
{
    NotificationSubscriptions::register([
        \App\Notifications\OrderShippedNotification::class,
        \App\Notifications\NewMessageNotification::class,
    ]);

    // Conditional registration
    if (config('features.slack_enabled')) {
        NotificationSubscriptions::register([
            \App\Notifications\SlackAlertNotification::class,
        ]);
    }
}
```

Don't forget to add this service provider to your `bootstrap/providers.php`:

```php
return [
    // ...
    App\Providers\NotificationSubscriptionsServiceProvider::class,
];
```

## Basic Usage

### Creating a Subscribable Notification

Transform any Laravel notification into a subscribable notification by implementing the `SubscribableNotification` interface and using the `DispatchesNotifications` trait:

```php
use App\Enums\NotificationChannel;
use Illuminate\Notifications\Notification;
use Codinglabs\NotificationSubscriptions\Concerns\DispatchesNotifications;
use Codinglabs\NotificationSubscriptions\Contracts\SubscribableNotification;

class OrderShippedNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public function __construct(
        public Order $order
    ) {}

    // Unique identifier for this notification type
    public static function type(): string
    {
        return 'order_shipped';
    }

    // Which channels this notification supports
    public static function channels(): array
    {
        return [NotificationChannel::DATABASE, NotificationChannel::MAIL];
    }

    // Who should receive this notification
    public function subscribers()
    {
        return collect([$this->order->user]);
    }

    // Standard Laravel notification methods
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Your order has shipped!')
            ->line("Order #{$this->order->id} is on its way.");
    }

    public function toArray($notifiable)
    {
        return [
            'title' => 'Order Shipped',
            'message' => "Order #{$this->order->id} has shipped.",
            'order_id' => $this->order->id,
        ];
    }
}
```

### Dispatching Notifications

Use the static `sendToSubscribers()` method instead of Laravel's standard notification sending:

```php
// This automatically:
// 1. Finds all subscribers
// 2. Checks each user's channel preferences
// 3. Applies rate limiting
// 4. Sends to appropriate channels only

OrderShippedNotification::sendToSubscribers($order);
```

### Transactional vs Subscribable Notifications

| Use Case | Method | Behavior |
|----------|--------|----------|
| **Transactional** (password reset, order confirmation) | Standard Laravel `$user->notify()` | Always sends, no filtering |
| **Subscribable** (messages, updates, marketing) | `Notification::sendToSubscribers()` | Respects user preferences |

```php
// Subscribable - respects user preferences
OrderShippedNotification::sendToSubscribers($order);

// Transactional - always sends (standard Laravel)
$user->notify(new PasswordResetNotification());
```

### How It Works

When a notification is dispatched:

1. **Subscriber lookup** - The `subscribers()` method determines who should receive the notification
2. **Channel filtering** - For each subscriber, the package checks their preferences:
   - If they have a stored preference for this notification type, only enabled channels are used
   - If no preference exists, channels with `defaultOn() === true` are used
3. **Rate limiting** - If a channel has rate limiting enabled and the notification has a subject, duplicate notifications are throttled
4. **Delivery** - The notification is sent only to the appropriate channels

## Channel Enum Reference

Your channel enum must implement `SubscribableChannel` with these methods:

| Method | Return Type | Description |
|--------|-------------|-------------|
| `driver()` | `string` | Laravel notification channel driver (e.g., `'database'`, `'mail'`, `OneSignalChannel::class`) |
| `label()` | `string` | Human-readable label for UI (e.g., `'Email'`, `'Push Notifications'`) |
| `isEnabled()` | `bool` | Whether this channel is currently available |
| `defaultOn()` | `bool` | Whether new users have this channel enabled by default |
| `hasRateLimiting()` | `bool` | Whether rate limiting applies to this channel |
| `rateLimitDuration()` | `int` | Rate limit duration in seconds |
| `isSystemChannel()` | `bool` | Whether users can opt out of this channel |

### Example: Advanced Channel Configuration

```php
use NotificationChannels\OneSignal\OneSignalChannel;

enum NotificationChannel: string implements SubscribableChannel
{
    case DATABASE = 'database';
    case MAIL = 'mail';
    case PUSH = OneSignalChannel::class;
    case SLACK = 'slack';

    public function driver(): string
    {
        return $this->value;
    }

    public function label(): string
    {
        return match ($this) {
            self::DATABASE => 'In-App',
            self::MAIL => 'Email',
            self::PUSH => 'Push Notifications',
            self::SLACK => 'Slack',
        };
    }

    public function isEnabled(): bool
    {
        return match ($this) {
            self::PUSH => config('services.onesignal.app_id') !== null,
            self::SLACK => config('services.slack.webhook_url') !== null,
            default => true,
        };
    }

    public function defaultOn(): bool
    {
        return match ($this) {
            self::PUSH, self::SLACK => false,
            default => true,
        };
    }

    public function hasRateLimiting(): bool
    {
        return match ($this) {
            self::DATABASE => false, // In-app doesn't need rate limiting
            default => true,
        };
    }

    public function rateLimitDuration(): int
    {
        return match ($this) {
            self::MAIL => 300,  // 5 minutes for emails
            self::PUSH => 60,   // 1 minute for push
            self::SLACK => 60,  // 1 minute for Slack
            default => config('notification-subscriptions.default_rate_limit_duration', 60),
        };
    }

    public function isSystemChannel(): bool
    {
        return $this === self::DATABASE; // Users can't opt out of in-app
    }
}
```

## Rate Limiting

Rate limiting prevents notification spam when the same notification could be triggered multiple times in quick succession.

### How Rate Limiting Works

Rate limits are applied per combination of:
- **Notification type** (e.g., `order_shipped`)
- **Channel** (e.g., `mail`)
- **Subject** (the model that triggered the notification)
- **Recipient** (the user receiving the notification)

### Subject Method

For rate limiting to work, your notification must return a subject:

```php
public function subject(): ?Model
{
    return $this->order;  // The model triggering the notification
}
```

If `subject()` returns `null`, rate limiting is skipped for that notification.

## Mandatory Channels

Some notifications must always be sent via certain channels regardless of user preferences. For example, a support ticket reply might always need an email notification, even if the user has opted out of email for other notifications.

### Defining Mandatory Channels

Override `mandatoryChannels()` in your notification class to specify channels that cannot be unsubscribed from:

```php
class TicketReplyNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public static function type(): string
    {
        return 'ticket_reply';
    }

    public static function channels(): array
    {
        return [NotificationChannel::DATABASE, NotificationChannel::MAIL, NotificationChannel::PUSH];
    }

    // Mail is mandatory — users cannot unsubscribe from it
    public static function mandatoryChannels(): array
    {
        return [NotificationChannel::MAIL];
    }

    // ...
}
```

By default, `mandatoryChannels()` returns an empty array, meaning all channels are optional.

### How Mandatory Channels Work

Mandatory channels are enforced at three levels:

1. **`shouldSend()` defense-in-depth** — When dispatching a notification, mandatory channels always return `true` in `shouldSend()`, even if the user's subscription record excludes them. This ensures delivery even with stale subscription data.

2. **Validation re-injection** — The `ValidatesNotificationPreferences` trait automatically re-injects mandatory channels into form requests during `prepareForValidation()`, so they can never be removed by user input.

3. **`NotificationPreferences` DTO** — The `getNotificationPreferences()` method populates a `mandatory` property on the DTO, mapping each notification type to its mandatory channel values. This allows your UI to render mandatory channels as disabled/locked checkboxes.

### Using Mandatory Data in the UI

The `NotificationPreferences` DTO includes a `mandatory` property:

```php
NotificationPreferences {
    types: [...],
    values: [...],
    mandatory: [
        'ticket_reply' => ['mail'],
    ],
}
```

Use this in your frontend to disable checkboxes for mandatory channels:

```vue
<input
    type="checkbox"
    :value="value"
    v-model="form[type]"
    :disabled="mandatory[type]?.includes(value)"
/>
```

> **Note:** System channels (where `isSystemChannel()` returns `true`) are already hidden from the UI entirely. Mandatory channels are different — they appear in the UI but cannot be unchecked.

## Building a Settings UI

The package provides a simple API for building notification preference UIs. The `HasNotificationSubscriptions` trait adds two methods to your User model:

- `getNotificationPreferences()` - Returns a DTO with `types` and `values` for the UI
- `updateNotificationPreferences(array $preferences)` - Updates preferences in the database

### Controller Setup

```php
use Codinglabs\NotificationSubscriptions\Concerns\ValidatesNotificationPreferences;

class NotificationSettingsController extends Controller
{
    public function edit()
    {
        return view('settings.notifications', [
            'preferences' => auth()->user()->getNotificationPreferences(),
        ]);
    }

    public function update(UpdateNotificationSettingsRequest $request)
    {
        auth()->user()->updateNotificationPreferences($request->validated());

        return redirect()->back()->with('success', 'Preferences saved.');
    }
}

// Form request - just add the trait, no configuration needed
class UpdateNotificationSettingsRequest extends FormRequest
{
    use ValidatesNotificationPreferences;
}
```

The `ValidatesNotificationPreferences` trait:
- Generates validation rules for each registered notification type
- Automatically re-injects system channels (users can't opt out of them)
- Automatically re-injects mandatory channels (users can't opt out of them either)

### The NotificationPreferences DTO

The `getNotificationPreferences()` method returns a `NotificationPreferences` object with two properties:

```php
NotificationPreferences {
    // Channel options for the UI (excludes system channels)
    types: [
        'order_shipped' => ['mail' => 'Email', 'slack' => 'Slack'],
        'new_message' => ['mail' => 'Email'],
    ],
    // User's current selections (or defaults)
    values: [
        'order_shipped' => ['mail'],
        'new_message' => ['mail', 'slack'],
    ],
    // Channels that cannot be unsubscribed from (excludes system channels)
    mandatory: [
        'order_shipped' => ['mail'],
    ],
}
```

### Example Blade Template

```blade
<form method="POST" action="{{ route('settings.notifications.update') }}">
    @csrf
    @method('PUT')

    @foreach($preferences->types as $notificationType => $channels)
        <div class="notification-group">
            <h3>{{ Str::title(str_replace('_', ' ', $notificationType)) }}</h3>

            @foreach($channels as $channel => $label)
                <label>
                    <input
                        type="checkbox"
                        name="{{ $notificationType }}[]"
                        value="{{ $channel }}"
                        @checked(in_array($channel, $preferences->values[$notificationType] ?? []))
                    />
                    {{ $label }}
                </label>
            @endforeach
        </div>
    @endforeach

    <button type="submit">Save Preferences</button>
</form>
```

### Inertia/Vue Example

```vue
<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ preferences: Object });

// Initialize form directly from values
const form = useForm({ ...props.preferences.values });
</script>

<template>
    <form @submit.prevent="form.put('/settings/notifications')">
        <div v-for="(channels, type) in preferences.types" :key="type">
            <h3>{{ type }}</h3>

            <label v-for="(label, value) in channels" :key="value">
                <input
                    type="checkbox"
                    :value="value"
                    v-model="form[type]"
                />
                {{ label }}
            </label>
        </div>

        <button type="submit">Save</button>
    </form>
</template>
```

## Advanced Usage

### Custom Notification Labels

Add metadata methods to your notifications for richer UIs:

```php
class OrderShippedNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public static function type(): string
    {
        return 'order_shipped';
    }

    // Custom methods for UI (not part of interface)
    public static function label(): string
    {
        return 'Order Shipping Updates';
    }

    public static function description(): string
    {
        return 'Get notified when your orders ship and are delivered.';
    }

    // ...
}
```

### Before Send Hook

Execute code before any notification is sent:

```php
class OrderShippedNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public static function beforeSend($notification): void
    {
        // Log, track analytics, modify notification, etc.
        Log::info('Sending order shipped notification', [
            'order_id' => $notification->order->id,
        ]);
    }

    // ...
}
```

### Custom Subscription Model

Extend the base model if you need additional functionality:

```php
// app/Models/NotificationSubscription.php
use Codinglabs\NotificationSubscriptions\Models\NotificationSubscription as BaseModel;

class NotificationSubscription extends BaseModel
{
    // Add custom methods, scopes, etc.
}

// config/notification-subscriptions.php
'subscription_model' => App\Models\NotificationSubscription::class,
```

## Database Schema

The package creates a `notification_subscriptions` table:

| Column | Type | Description |
|--------|------|-------------|
| id | bigint | Primary key |
| user_id | bigint | Foreign key to users table |
| type | string | Notification type identifier |
| channels | json | Array of enabled channel names |
| created_at | timestamp | Creation timestamp |
| updated_at | timestamp | Last update timestamp |

A unique constraint ensures one subscription record per user/type combination.

## Testing

```bash
composer test
```

## Credits

- [Coding Labs](https://github.com/codinglabsau)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
