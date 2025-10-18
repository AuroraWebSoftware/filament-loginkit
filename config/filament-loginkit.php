<?php

use AuroraWebSoftware\FilamentLoginKit\Enums\TwoFactorType;
use AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth\Login;
use AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth\LoginTwoFactor;
use AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth\PasswordConfirmation;
use AuroraWebSoftware\FilamentLoginKit\Http\Livewire\Auth\Register;
use AuroraWebSoftware\FilamentLoginKit\Pages\Account;
use Filament\Auth\Pages\PasswordReset\RequestPasswordReset;
use Filament\Auth\Pages\PasswordReset\ResetPassword;

return [

    'email_login' => env('LOGINKIT_EMAIL_LOGIN_ENABLED', true),

    'sms_login' => env('LOGINKIT_SMS_LOGIN_ENABLED', true),

    'reset_password_enabled' => true,

    /*
    |--------------------------------------------------------------------------
    | Two Factor Authentication
    |--------------------------------------------------------------------------
    |
    | This value determines which two factor authentication options are available.
    | Simply add or remove the options you want to use.
    |
    | Available options: email, sms, authenticator
    |
    */
    'options' => [
        TwoFactorType::authenticator,
        TwoFactorType::email,
        TwoFactorType::sms,
    ],

    'email_view' => null,

    'email_app_name' => config('app.name', 'filament-loginkit'),

    'enabled_features' => [
        /*
        |--------------------------------------------------------------------------
        | Register
        |--------------------------------------------------------------------------
        |
        | This value determines whether users may register in the application.
        |
        */
        'register' => false,

        /*
        |--------------------------------------------------------------------------
        | Tenant
        |--------------------------------------------------------------------------
        |
        | Set to true if you're using Filament in a multi-tenant setup. If true, you
        | need to manually set the user menu item for the two factor authentication
        | page panel class. Take a look at the documentation for more information.
        |
        */
        'multi_tenancy' => false,

        //        'ip_rate_limit' => env('LOGIN_KIT_IP_LIMIT', false),

        //        'generic_errors' => env('LOGIN_KIT_GENERIC_ERR', false),
        'generic_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Service
    |--------------------------------------------------------------------------
    |
    | To use an SMS service, you need to install the corresponding package.
    | You then have to create a App\Notifications\SendOTP class that extends
    | the AuroraWebSoftware\TwoFactorAuth\Notifications\SendOTP class. After that,
    | you can set the class alias in the sms_service key.
    |
    */
    'sms_service_class' => \App\Services\SmsService::class,
    'send_otp_class' => \AuroraWebSoftware\FilamentLoginKit\Notifications\SendOTP::class,
    'phone_number_field' => 'phone',

    'queue_notifications' => env('LOGINKIT_QUEUE_NOTIFICATIONS', true),
    'email_queue' => env('LOGINKIT_EMAIL_QUEUE', 'filament-loginkit'),
    'sms_queue' => env('LOGINKIT_SMS_QUEUE', 'filament-loginkit'),

    'notification_max_tries' => 1,
    'notification_retry_delay' => 30,
    'notification_backoff' => 0,

    'turnstile' => [
        'enabled' => env('LOGINKIT_TURNSTILE_ENABLED', false),
        'sitekey' => env('LOGINKIT_TURNSTILE_SITEKEY'),
        'secret' => env('LOGINKIT_TURNSTILE_SECRET'),
    ],

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

    //    'ip_limit' => [
    //        'max_attempts' => env('LOGIN_KIT_IP_MAX', 20),
    //        'decay_minutes' => env('LOGIN_KIT_IP_TTL', 1),
    //    ],

    'sms' => [
        'code_length' => 6,

        'code_ttl' => 5,

        'max_wrong_attempts' => 5,

        'wrong_attempt_decay' => 300,

        'resend_cooldown' => 90,

        'flood' => [
            'max_per_window' => 10,
            'window_minutes' => 10,
        ],

        'resend' => [
            'max_requests' => 3,
            'window_minutes' => 10,
        ],
    ],

    'brand_logo' => env('FILAMENT_BRAND_LOGO'),

    'email' => [
        'from_name' => env('MAIL_FROM_NAME', config('app.name')),
        'from_address' => env('MAIL_FROM_ADDRESS', 'noreply@example.com'),
    ],

    'account_page' => [
        'can_edit' => env('LOGINKIT_ACCOUNT_PAGE_CAN_EDIT', false),
        '2fa' => [
            'code_length' => 6,
            'code_ttl' => 5,
        ],
        '2fa_settings_visibility' => env('LOGINKIT_ACCOUNT_PAGE_2FA_SETTINGS_VISIBILITY', false),
        'rate_limits' => [
            'max_requests' => 5,
            'per_minutes' => 1,
        ]
    ],

    'twilio' => [
        'enabled' => env('LOGINKIT_TWILIO_ENABLED', false),
        'sid' => env('LOGINKIT_TWILIO_SID'),
        'token' => env('LOGINKIT_TWILIO_TOKEN'),
        'whatsapp_from' => env('LOGINKIT_TWILIO_WHATSAPP_FROM'),
        'whatsapp_template_sid' => env('LOGINKIT_TWILIO_WHATSAPP_TEMPLATE_SID'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | If you want to customize the pages, you can override the used classes here.
    | Make your that your classes extend the original classes.
    |
    */
    'login' => Login::class,
    'register' => Register::class,
    'challenge' => LoginTwoFactor::class,
    'two_factor_settings' => Account::class,
    'password_reset' => ResetPassword::class,
    'password_confirmation' => PasswordConfirmation::class,
    'request_password_reset' => RequestPasswordReset::class,
];
