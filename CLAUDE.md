# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Laravel package for managing user notification subscriptions across multiple notification types and channels. Built using Spatie's Laravel Package Tools.

## Commands

**Run tests:**
```bash
composer test
```

**Run a single test:**
```bash
vendor/bin/pest --filter "test name"
```

**Code style check:**
```bash
vendor/bin/pint --test
```

**Fix code style:**
```bash
vendor/bin/pint
```

## Architecture

### Core Components

- `src/Contracts/SubscribableNotification.php` - Interface for notifications that support subscription management
- `src/Concerns/DispatchesNotifications.php` - Trait with `shouldSend()` logic, rate limiting, and channel filtering
- `src/Enums/ChannelType.php` - Config-driven enum for notification channels (DATABASE, MAIL, BROADCAST)
- `src/Models/NotificationSubscription.php` - Eloquent model storing user channel preferences per notification type

### User Integration

- `src/Traits/HasNotificationSubscriptions.php` - Add to User model for `notificationSubscriptions()` relationship

### Request Helpers

- `src/Traits/ValidatesNotifications.php` - Form request validation for subscription updates
- `src/Traits/PreparesNotificationsForEditing.php` - Prepares notification data for settings UI

### Configuration

- `config/notification-subscriptions.php` - Channel definitions (enabled, default_on, rate_limited, label), user model, table name

### Database

- `database/migrations/` - Creates `notification_subscriptions` table with `user_id`, `type`, and `channels` (JSON) columns

## Key Patterns

**Notification Implementation:**
```php
class MyNotification extends Notification implements SubscribableNotification
{
    use DispatchesNotifications;

    public static function type(): string { return 'my_notification'; }
    public static function channels(): array { return [ChannelType::DATABASE, ChannelType::MAIL]; }
}
```

**Channel Configuration:** Channels read settings from config - `isEnabled()`, `defaultOn()`, `hasRateLimiting()`, `label()`

## Testing

Uses Pest with Orchestra Testbench. Tests extend `TestCase` which sets up in-memory SQLite with required tables.

## Requirements

- PHP 8.3+
- Laravel 11 or 12
