<?php

return [
    'default' => 'Your login code: :code',
    '2fa' => 'Your 2FA code: :code. Do not share this with anyone.',
    'password_reset' => 'Your password reset code: :code',

    'email_login_tab' => 'Login with Email',
    'sms_login_tab' => 'Login with Phone',

    'form' => [
        'email' => 'Email',
        'password' => 'Password',
        'remember' => 'Remember Me',
    ],

    'security' => [
        'required_title' => 'Security Verification Required',
        'required_body' => 'Please complete the security verification.',
        'failed_title' => 'Verification Failed',
        'failed_body' => 'Security verification failed, please try again.',
    ],

    'ip' => [
        'limited_title' => 'IP Address Blocked',
        'limited_body' => 'Too many attempts from this IP address. Please try again later.',
    ],

    'sms' => [
        'phone_label' => 'Phone Number',
        'code_label' => 'Verification Code',
        'phone_format_error' => 'The phone number must be in the format +90 5XX XXX XX XX.',
        'code_format_error' => 'The verification code must be :length digits.',
        'phone_title' => 'Enter Your Phone Number',
        'phone_instruction' => 'You can log in via SMS by entering your registered phone number.',
        'send_code' => 'Send SMS Code',
        'login' => 'Login with SMS',
        'login_whatsapp' => 'Login with Whatsapp',
        'code_title' => 'Verification Code',
        'code_instruction' => 'Enter the :length-digit code sent to :number.',
        'verify' => 'Verify',
        'back' => 'Go Back',
        'not_received' => "Didn't receive the code?",
        'resend' => 'Resend',
        'code_expires' => 'The code will expire in :minutes minutes',
        'code_expires_text' => 'The code will expire in :minutes minutes',
        'sent_title' => 'SMS Sent',
        'sent_body' => 'A verification code has been sent to your phone number.',
        'resent_title' => 'SMS Resent',
        'resent_body' => 'A new verification code has been sent to your phone number.',
        'not_found_title' => 'Phone Number Not Found',
        'not_found_body' => 'No user found with this phone number.',
        'invalid_code_title' => 'Invalid Code',
        'invalid_code_body' => 'The verification code you entered is invalid or has expired.',
        'max_wrong_title' => 'Too Many Incorrect Attempts',
        'max_wrong_body' => 'You have entered too many incorrect codes. Please try again later.',
        'too_many_attempts_title' => 'Too Many Attempts',
        'too_many_attempts_body' => 'You have made too many SMS requests. Please try again in :seconds seconds.',
        'too_many_requests_title' => 'Limit Exceeded',
        'too_many_requests_body' => 'Daily SMS limit exceeded for this phone number.',
        'captcha_required_title' => 'Security Verification',
        'captcha_required_body' => 'Please complete the security verification.',
        'captcha_failed_title' => 'Verification Failed',
        'captcha_failed_body' => 'Security verification failed, please try again.',
        'invalid_session_title' => 'Invalid Session',
        'invalid_session_body' => 'Session is invalid, please start again.',
        'resend_too_many_title' => 'Too Many Resend Attempts',
        'resend_too_many_body' => 'Too many resend attempts. Please wait :seconds seconds.',
        'resend_limit_title' => 'Resend Limit',
        'resend_limit_body' => 'Resend limit exceeded for this phone number. Please wait :window_minutes minutes.',
        'generic_fail_title' => 'Operation Failed',
        'generic_fail_body' => 'The operation failed, please try again.',
        'generic_title' => 'Error',
        'generic_body' => 'An error occurred, please try again.',
        'code' => 'Code',

        'inactive_title' => 'Login blocked',
        'inactive_body' => 'Your account is inactive. Please contact the administrator.',
    ],

    'email' => [
        'subject' => 'Two-Factor Authentication Code',
        'header_subtitle' => 'Secure Login System',
        'greeting' => 'Hello :name!',
        'dear_user' => 'Dear User',
        'description' => 'To securely access your account, use the verification code below. This code is for one-time use only and will expire after 10 minutes.',
        'code_label' => 'Verification Code',
        'expires_in' => 'Expires in :minutes minutes',
        'how_to_use' => 'How to Use?',
        'step_1' => 'Enter this code on the login page in the verification field',
        'step_2' => 'Type the code exactly as shown above',
        'step_3' => 'Complete your login process',
        'security_notice' => 'Security Notice',
        'security_warning' => "If you didn't perform this login, please change your password immediately to protect your account and contact us.",
        'footer_text' => 'This email was sent automatically by :app.',

        'inactive_title' => 'Login blocked',
        'inactive_body' => 'Your account is inactive. Please contact the administrator.',
    ],

    'navigation' => [
        'account' => 'Account Settings',
        'my_account' => 'My Account',
    ],

    'account' => [
        'title' => 'Account Settings',
        'user_information' => 'User Information',
        'user_information_description' => 'You can update your account information here.',
        'change_password' => 'Change Password',
        'change_password_description' => 'For your security, we recommend regularly changing your password.',
        'save' => 'Save',
        'saving' => 'Saving...',
        'changing_password' => 'Changing...',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'phone' => 'Phone',
        'current_password' => 'Current Password',
        'new_password' => 'New Password',
        'new_password_confirmation' => 'New Password (Repeat)',
        'password_requirements' => 'Must contain at least 8 characters, an uppercase letter, a lowercase letter, and a number.',
    ],

    'two_factor' => [
        'title' => 'Two-Factor Authentication',
        'description' => 'Enhance your account security by enabling two-factor authentication.',
        'enabled' => '2FA Enabled',
        'disabled' => '2FA Disabled',
        'protected_with' => 'Your account is protected with',
        'password_only' => 'Your account is protected only by password',
        'enable' => 'Enable 2FA',
        'disable' => 'Disable',
        'disable_confirm' => 'Are you sure you want to disable 2FA? This will reduce your account security.',
        'regenerate_codes' => 'Regenerate Codes',
        'regenerate_codes_confirm' => 'Are you sure you want to generate new recovery codes? Old codes will be invalid.',
        'setup_title' => '2FA Setup',
        'select_method' => 'Select your security method',
        'method' => '2FA Method',
        'logout' => 'Logout',

        'methods' => [
            'sms' => 'SMS',
            'email' => 'Email',
            'authenticator' => 'Authenticator App',
        ],

        'descriptions' => [
            'sms' => 'A 6-digit security code will be sent to your phone via SMS',
            'email' => 'A 6-digit security code will be sent to your email address',
            'authenticator' => 'Use Google Authenticator, Authy, or a similar app',
        ],

        'scan_qr_code' => 'Scan the QR code',
        'scan_with_app' => 'Scan this QR code with your authenticator app',
        'manual_key' => 'Manual Entry Key',
        'enter_code' => 'Enter verification code',
        'enter_app_code' => 'Enter the 6-digit code from your authenticator app',
        'enter_sent_code' => 'Enter the 6-digit verification code sent to you',
        'confirm_and_enable' => 'Confirm and Enable',
        'confirming' => 'Confirming...',

        'recovery_codes' => 'Recovery Codes',
        'download_and_close' => 'Download and Close',
        'recovery_codes_warning' => 'Important: Each code can only be used once. If you lose access to your phone, you can log in with these codes.',
        'recovery_codes_info' => 'If you lose access to your phone, you can use these codes. Use one of these codes when logging in.',

        'account' => 'Account',
        'generated_at' => 'Generated At',
        'warning_save_securely' => 'WARNING: Keep these codes in a safe place!',
        'each_code_once' => 'Each code can only be used once.',

        'heading' => 'Authenticate with your code',
        'invalid_code' => 'Invalid verification code.',
        'code_label' => 'Code',
        'resend' => 'Resend',
        'login' => 'Login',
    ],

    'common' => [
        'cancel' => 'Cancel',
        'continue' => 'Continue',
        'preparing' => 'Preparing...',
        'save' => 'Save',
        'close' => 'Close',
        'download' => 'Download',
    ],

    'notifications' => [
        'success' => 'Success!',
        'error' => 'Error!',
        'warning' => 'Warning!',
        'info' => 'Info!',

        // Account notifications
        'account_updated' => 'Your account information has been updated.',
        'account_update_failed' => 'An error occurred while updating your information.',
        'current_password_incorrect' => 'Your current password is incorrect.',
        'password_changed' => 'Your password has been changed successfully.',
        'password_change_failed' => 'An error occurred while changing your password.',

        // 2FA notifications
        'two_factor_already_enabled' => '2FA is already enabled.',
        'two_factor_already_disabled' => '2FA is already disabled.',
        'select_two_factor_method' => 'Please select a 2FA method.',
        'qr_code_ready' => 'QR Code Ready!',
        'scan_qr_code' => 'Scan the QR code with your authenticator app.',
        'authenticator_setup_failed' => 'Authenticator setup failed.',
        'verification_code_send_failed' => 'Failed to send verification code.',
        'code_sent' => 'Code Sent!',
        'code_sent_to' => 'Verification code sent via :method.',
        'enter_six_digit_code' => 'Please enter the 6-digit verification code.',
        'invalid_verification_code' => 'Invalid verification code.',
        'two_factor_enabled' => '2FA (:method) enabled successfully.',
        'two_factor_disabled' => '2FA has been disabled.',
        'two_factor_disable_failed' => 'An error occurred while disabling 2FA.',
        'recovery_codes_only_authenticator' => 'Recovery codes are only used for Authenticator.',
        'recovery_codes_generated' => 'New recovery codes have been generated.',
        'recovery_codes_generation_failed' => 'An error occurred while generating recovery codes.',
        'recovery_codes_downloaded' => 'Recovery codes have been downloaded and saved.',
    ],

    'confirm' => 'Confirm',
    'cancel' => 'Cancel',
    'approve' => 'Yes, Approve',

    'regenerate_codes_confirm' => 'Are you sure you want to regenerate recovery codes? Existing codes will be invalid!',

    'disable_confirm' => 'Are you sure you want to disable two-factor authentication?',

    'please_select_2fa' => 'Please select a two-factor authentication method.',
    'please_select_2fa_description' => 'To increase the security of your account, you must set up two-factor authentication.',

    'twillio' => [
        'whatsapp_login_message' => 'Your login code is: :code. The code is valid for :minutes minutes.',
    ]
];
