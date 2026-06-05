<?php

return [
    'auth' => [
        'login_max_attempts' => (int) env('SECURITY_LOGIN_MAX_ATTEMPTS', 5),
        'login_decay_minutes' => (int) env('SECURITY_LOGIN_DECAY_MINUTES', 15),
        'register_max_attempts' => (int) env('SECURITY_REGISTER_MAX_ATTEMPTS', 3),
        'register_decay_minutes' => (int) env('SECURITY_REGISTER_DECAY_MINUTES', 60),
        'password_reset_max_attempts' => (int) env('SECURITY_PASSWORD_RESET_MAX_ATTEMPTS', 3),
        'password_reset_decay_minutes' => (int) env('SECURITY_PASSWORD_RESET_DECAY_MINUTES', 60),
        'brute_force_base_seconds' => (int) env('SECURITY_BRUTE_FORCE_BASE_SECONDS', 2),
        'brute_force_max_seconds' => (int) env('SECURITY_BRUTE_FORCE_MAX_SECONDS', 900),
        'suspicious_ip_window_minutes' => (int) env('SECURITY_SUSPICIOUS_IP_WINDOW_MINUTES', 60),
        'suspicious_ip_threshold' => (int) env('SECURITY_SUSPICIOUS_IP_THRESHOLD', 5),
        'rapid_request_window_seconds' => (int) env('SECURITY_RAPID_REQUEST_WINDOW_SECONDS', 10),
        'rapid_request_threshold' => (int) env('SECURITY_RAPID_REQUEST_THRESHOLD', 20),
    ],

    'upload' => [
        'max_size_kb' => (int) env('SECURITY_UPLOAD_MAX_SIZE_KB', 8192),
        'allowed_image_mimes' => ['image/jpeg', 'image/png', 'image/webp'],
        'virus_scan_enabled' => (bool) env('SECURITY_VIRUS_SCAN_ENABLED', false),
        'clamav_host' => env('SECURITY_CLAMAV_HOST', '127.0.0.1'),
        'clamav_port' => (int) env('SECURITY_CLAMAV_PORT', 3310),
    ],

    'headers' => [
        'frame_options' => env('SECURITY_HEADER_FRAME_OPTIONS', 'SAMEORIGIN'),
        'referrer_policy' => env('SECURITY_HEADER_REFERRER_POLICY', 'strict-origin-when-cross-origin'),
        'permissions_policy' => env('SECURITY_HEADER_PERMISSIONS_POLICY', 'camera=(), microphone=(), geolocation=()'),
        'hsts_max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
    ],

    'csrf' => [
        'cookie_name' => 'XSRF-TOKEN',
        'header_name' => 'X-XSRF-TOKEN',
        'token_ttl_minutes' => (int) env('SECURITY_CSRF_TOKEN_TTL_MINUTES', 120),
    ],
];
