<?php

return [
    // Brand signature appended to SMS messages
    'brand' => env('PARKING_BRAND', 'Qelale'),

    // Link used in checkout SMS for app & payment instructions
    'instructions_url' => env('PARKING_INSTRUCTIONS_URL', 'http://bit.ly/3TD4UV5'),

    // Human-readable rate text shown in checkout SMS
    'rate_text' => env('PARKING_RATE_TEXT', '20.00 ETB / 30 minutes'),

    // Pricing engine parameters
    'block_minutes' => env('PARKING_BLOCK_MINUTES', 30), // minutes per billing block
    'rate_per_30_min' => env('PARKING_RATE_PER_30_MIN', 20), // ETB per block
    'min_charge' => env('PARKING_MIN_CHARGE', 20), // ETB minimum

    // Date/time display format for SMS (12-hour with AM/PM)
    'time_format' => env('PARKING_TIME_FORMAT', 'M d, Y h:i A'),
];
