<?php

return [
    'length' => env('OTP_LENGTH', 6),
    'expiry' => env('OTP_EXPIRY_MINUTES', 5),
    'max_attempts' => env('OTP_MAX_ATTEMPTS', 3),
    'sms_enabled' => env('OTP_SMS_ENABLED', true),
    'sms_api_key' => env('SMS_ETHIOPIA_KEY'),
    'sms_api_url' => env('SMS_ETHIOPIA_URL', 'https://smsethiopia.et/api/sms/send'),
];
