<?php

return [

    'otp' => [
        'length' => 4,
        'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 5),
        'max_verify_attempts' => (int) env('OTP_MAX_VERIFY_ATTEMPTS', 5),
        'progressive_delays' => [30, 60, 120],
        // Resend hardening (audit #4): server cooldown + per-window cap + long lock.
        'resend_cooldown_seconds' => (int) env('OTP_RESEND_COOLDOWN_SECONDS', 60),
        'max_resends_per_window' => (int) env('OTP_MAX_RESENDS_PER_WINDOW', 5),
        'resend_lock_minutes' => (int) env('OTP_RESEND_LOCK_MINUTES', 30),
    ],

    'device_limit' => [
        'max_accounts_per_device' => (int) env('DEVICE_MAX_ACCOUNTS', 3),
        'cookie_name' => 'device_id',
        'cookie_minutes' => 2628000,
    ],

    'queues' => [
        'sms' => env('QUEUE_SMS', 'sms-high'),
        'email' => env('QUEUE_EMAIL', 'email-high'),
        'auth' => env('QUEUE_AUTH', 'auth-critical'),
    ],

];
