# Filament Loginkit

A flexible authentication kit for [Filament](https://filamentphp.com/) that brings enhanced two-factor authentication,
SMS login, and customizable login flows to your Laravel applications.

## Table of Contents

- [Installation](#installation)
- [User Model Changes](#user-model-changes)
- [Configuration](#configuration)
    - [Login Methods](#login-methods)
    - [Two-Factor Authentication](#two-factor-authentication)
    - [Features](#features)
    - [SMS Service](#sms-service)
    - [Queues](#queues)
    - [Rate Limits](#rate-limits)
    - [Branding](#branding)
- [Changelog](#changelog)
- [Contributing](#contributing)
- [Security Vulnerabilities](#security-vulnerabilities)
- [Credits](#credits)
- [License](#license)

## Installation

First, install the package via Composer:

```bash
composer require aurorawebsoftware/filament-loginkit
```

Then, run the install command to set up configuration, migrations, assets, and required dependencies:

```bash
php artisan filament-loginkit:install
```

## User Model Changes

Make sure your User model implements the necessary properties and traits:

```php
<?php

use AuroraWebSoftware\FilamentLoginKit\Traits\TwoFactorAuthenticatable;

class User extends Authenticatable
{
    use TwoFactorAuthenticatable;

    protected $fillable = [
        'phone_number',
        'two_factor_type',
        'is_2fa_required',
        'sms_login_code',
        'sms_login_expires_at',
        'two_factor_code',
        'two_factor_expires_at',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'sms_login_expires_at' => 'datetime',
        'is_2fa_required' => 'boolean',
        'two_factor_expires_at' => 'datetime',
    ];
}
```

## Configuration

After publishing the config file, you can find it at `config/filament-loginkit.php`.

### Login Methods

Enable/disable email and SMS login:

```php
'email_login' => env('LOGINKIT_EMAIL_LOGIN_ENABLED', true),

'sms_login' => env('LOGINKIT_SMS_LOGIN_ENABLED', false),
```

### Two-Factor Authentication

Set available two-factor methods: `authenticator`, `email`, `sms`

```php
'options' => [
    \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::authenticator,
    \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::email,
    \AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType::sms,
],
```

### Features

Enable or disable registration, multi-tenancy, or generic error messages:

```php
'enabled_features' => [
    'register' => false,
    'multi_tenancy' => false,
    'generic_errors' => false,
],
```

### SMS Service

To enable SMS authentication, you must implement your own SMS service using the provided interface.

You can find the interface at:

```php
AuroraWebSoftware\FilamentLoginKit\Contracts\SmsServiceInterface
```

First, create a class that implements this interface. For example:

```php
namespace App\Services;

use AuroraWebSoftware\FilamentLoginKit\Contracts\SmsServiceInterface;

class SmsService implements SmsServiceInterface
{
    public function sendSms(string $phone, string $message): void
    {
        // Your SMS sending logic here
    }
}
```

Then, register your service in the configuration file:

```php
// config/filament-loginkit.php
'sms_service_class' => \App\Services\SmsService::class,
```

**Note:** Your SMS service must implement all methods required by `SmsServiceInterface`.


### Queues

Set whether notifications are queued:

```php
'queue_notifications' => env('LOGINKIT_QUEUE_NOTIFICATIONS', true),
'email_queue' => env('LOGINKIT_EMAIL_QUEUE', 'filament-loginkit'),
'sms_queue' => env('LOGINKIT_SMS_QUEUE', 'filament-loginkit'),
```

The queue name used by Filament Loginkit is `filament-loginkit`.

To start the queue worker in Laravel, run the following command:

```bash
php artisan queue:work --queue=filament-loginkit
```

### Rate Limits

Limit login and 2FA attempts to prevent abuse:

```php
'rate_limits' => [
    'sms' => [
        'max_requests' => 5,
        'per_minutes' => 1,
    ],
    'sms_resend' => [
        'max_requests' => 2,
        'per_minutes' => 1,
    ],
    'login' => [
        'max_requests' => 5,
        'per_minutes' => 1,
    ],
    'two_factor' => [
        'max_requests' => 5,
        'per_minutes' => 1,
    ],
],
```

**And more!** Please check the `config/filament-loginkit.php` file for the full list of options.

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review our [security policy](SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Aurora Web Software](https://github.com/aurorawebsoftware)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
