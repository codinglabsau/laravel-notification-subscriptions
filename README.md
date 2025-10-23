# laravel-notification-subscriptions for Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/codinglabsau/laravel-notification-subscriptions.svg?style=flat-square)](https://packagist.org/packages/codinglabsau/laravel-notification-subscriptions)
[![Test](https://github.com/codinglabsau/laravel-notification-subscriptions/actions/workflows/run-tests.yml/badge.svg)](https://github.com/codinglabsau/laravel-notification-subscriptions/actions/workflows/run-tests.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/codinglabsau/laravel-notification-subscriptions.svg?style=flat-square)](https://packagist.org/packages/codinglabsau/laravel-notification-subscriptions)

Laravel Notification Subscriptions manages user notification subscriptions across multiple notification types and channels.

Here is a bit more about why it is awesome.

- awesome!
- much awesome!

___
## Installation

### Install With Composer
```bash
composer require codinglabsau/laravel-notification-subscriptions
```

### Database Migrations
```bash
php artisan vendor:publish --tag="laravel-notification-subscriptions-migrations"
php artisan migrate
```

### Publish Configuration
```bash
php artisan vendor:publish --tag="laravel-notification-subscriptions-config"
```

## Usage
Create a new feature in the database and set the initial state:
```php
use Codinglabs\NotificationSubscriptions;

// ...
```

## Testing
```bash
composer test
```

## Security Vulnerabilities
Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits
- [Steve Thomas](https://github.com/stevethomas)
- [All Contributors](../../contributors)

## License
The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
