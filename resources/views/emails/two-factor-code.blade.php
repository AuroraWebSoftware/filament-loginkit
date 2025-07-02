<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ __('filament-loginkit::filament-loginkit.email.subject') }}</title>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            line-height: 1.6;
            color: #1f2937;
            background-color: #f9fafb;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .email-wrapper {
            width: 100%;
            background-color: #f9fafb;
            padding: 20px 0;
            min-height: 100vh;
        }

        .email-container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            overflow: hidden;
            border: 1px solid #e5e7eb;
        }

        .email-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 30px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .email-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.15"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            pointer-events: none;
        }

        .logo-container {
            position: relative;
            z-index: 1;
        }

        .logo {
            max-height: 60px;
            height: auto;
            display: inline-block;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.2));
        }

        .app-name {
            font-size: 28px;
            font-weight: 800;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            letter-spacing: -0.5px;
            position: relative;
            z-index: 1;
        }

        .email-content {
            padding: 50px 40px;
            text-align: center;
        }

        .security-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981, #059669);
            border-radius: 50%;
            margin: 0 auto 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(16, 185, 129, 0.3);
        }

        .security-icon::before {
            content: '🔐';
            font-size: 36px;
        }

        .greeting {
            font-size: 24px;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 16px;
            line-height: 1.3;
        }

        .code-container {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 2px solid #0ea5e9;
            border-radius: 16px;
            padding: 30px;
            margin: 40px 0;
            position: relative;
            overflow: hidden;
        }

        .code-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #3b82f6, #1d4ed8, #3b82f6);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
        }

        @keyframes shimmer {
            0% {
                background-position: -200% 0;
            }
            100% {
                background-position: 200% 0;
            }
        }

        .code-label {
            font-size: 14px;
            font-weight: 600;
            color: #0369a1;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
        }

        .verification-code {
            font-size: 36px;
            font-weight: 800;
            color: #0c4a6e;
            font-family: 'Courier New', monospace;
            letter-spacing: 8px;
            text-shadow: 0 2px 4px rgba(12, 74, 110, 0.1);
            margin: 16px 0;
            user-select: all;
            -webkit-user-select: all;
            -moz-user-select: all;
            -ms-user-select: all;
        }

        .code-expires {
            font-size: 13px;
            color: #64748b;
            font-weight: 500;
        }

        .security-warning {
            background: linear-gradient(135deg, #fef3cd, #fde68a);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            margin: 30px 0;
        }

        .warning-title {
            font-size: 16px;
            font-weight: 700;
            color: #92400e;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .warning-title::before {
            content: '⚠️';
            margin-right: 8px;
        }

        .warning-text {
            font-size: 14px;
            color: #78350f;
            line-height: 1.5;
            text-align: center;
        }

        .email-footer {
            background-color: #f8fafc;
            padding: 30px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }

        .footer-text {
            font-size: 13px;
            color: #64748b;
            margin-bottom: 8px;
        }

        /* Responsive Design */
        @media only screen and (max-width: 600px) {
            .email-container {
                margin: 10px;
                border-radius: 12px;
            }

            .email-header {
                padding: 30px 20px;
            }

            .app-name {
                font-size: 24px;
            }

            .email-content {
                padding: 40px 25px;
            }

            .greeting {
                font-size: 22px;
            }

            .verification-code {
                font-size: 32px;
                letter-spacing: 6px;
            }

            .email-footer {
                padding: 25px 20px;
            }
        }
    </style>
</head>
<body>
<div class="email-wrapper">
    <div class="email-container">
        <div class="email-header">
            <div class="logo-container">
                @if(config('filament.brand_logo'))
                    <img src="{{ asset(config('filament.brand_logo')) }}" alt="{{ config('app.name') }}" class="logo">
                @else
                    <div class="app-name">{{ config('app.name') }}</div>
                @endif
            </div>
        </div>

        <div class="email-content">
            <div class="security-icon"></div>

            <h1 class="greeting">
                {{ __('filament-loginkit::filament-loginkit.email.greeting', ['name' => $user->name ?? __('filament-loginkit::filament-loginkit.email.dear_user')]) }}
            </h1>

            <div class="code-container">
                <div class="code-label">{{ __('filament-loginkit::filament-loginkit.email.code_label') }}</div>
                <div class="verification-code">{{ $code }}</div>
                <div class="code-expires">
                    {{ __('filament-loginkit::filament-loginkit.email.expires_in', [
                        'minutes' => config('filament-loginkit.account_page.2fa.code_ttl', 5)
                    ]) }}
                </div>
            </div>

            <div class="security-warning">
                <div class="warning-title">{{ __('filament-loginkit::filament-loginkit.email.security_notice') }}</div>
                <div class="warning-text">
                    {{ __('filament-loginkit::filament-loginkit.email.security_warning') }}
                </div>
            </div>
        </div>

        <div class="email-footer">
            <div class="footer-text">
                {{ __('filament-loginkit::filament-loginkit.email.footer_text', ['app' => config('app.name')]) }}
            </div>
        </div>
    </div>
</div>
</body>
</html>
