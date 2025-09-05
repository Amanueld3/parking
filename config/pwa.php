<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Would you like the install button to appear on all pages?
      Set true/false
    |--------------------------------------------------------------------------
    */

    'install-button' => true,

    /*
    |--------------------------------------------------------------------------
    | PWA Manifest Configuration
    |--------------------------------------------------------------------------
    |  php artisan erag:update-manifest
    */

    'manifest' => [
        // A unique identifier for the app (required by some platforms)
        'id' => '/',

        // Document language and text direction
        'lang' => 'en',
        'dir' => 'ltr',

        'name' => 'Laravel PWA',
        'short_name' => 'LPT',
        // Where the app should start when launched
        'start_url' => '/',
        // Limit navigation scope to your site root
        'scope' => '/',
        // Orientation hint
        'orientation' => 'portrait',
        'background_color' => '#6777ef',
        'display' => 'fullscreen',
        'description' => 'A Progressive Web Application setup for Laravel projects.',
        'theme_color' => '#6777ef',
        // Categories for stores & OS
        'categories' => ['productivity', 'utilities'],
        // IARC rating id (placeholder for development)
        'iarc_rating_id' => 'IARC-DEV-PLACEHOLDER',
        // Prefer related app stores?
        'prefer_related_applications' => false,
        // Related apps list (example)
        'related_applications' => [
            [
                'platform' => 'play',
                'url' => 'https://play.google.com/store/apps/details?id=com.example.parking',
                'id' => 'com.example.parking',
            ],
        ],
        // Chromium launch handler object
        'launch_handler' => [
            'client_mode' => 'auto', // valid values include: auto, navigate-existing, focus-existing
        ],
        // Screenshots array (use available assets for now)
        'screenshots' => [
            [
                'src' => '/logo.png',
                'sizes' => '512x512',
                'type' => 'image/png',
                'form_factor' => 'wide',
            ],
        ],
        // Scope extensions with origin
        'scope_extensions' => [
            [
                'origin' => env('APP_URL', 'http://localhost'),
            ],
        ],
        'icons' => [
            [
                'src' => 'logo.png',
                'sizes' => '192x192',
                'type' => 'image/png',
            ],
            [
                'src' => 'logo.png',
                'sizes' => '512x512',
                'type' => 'image/png',
            ],
        ],

        // Prefer window-controls overlay, fallback to standalone
        'display_override' => ['window-controls-overlay', 'standalone'],

        // Let the app handle links preferentially
        'handle_links' => 'preferred',

        // Microsoft Edge Side Panel support
        'edge_side_panel' => [
            'preferred_width' => 400,
        ],

        // File handlers (example types)
        'file_handlers' => [
            [
                'action' => '/files/open',
                'accept' => [
                    'text/csv' => ['.csv'],
                    'application/json' => ['.json'],
                ],
            ],
        ],

        // Protocol handlers
        'protocol_handlers' => [
            [
                'protocol' => 'web+parking',
                'url' => '/protocol?url=%s',
            ],
        ],

        // Share target
        'share_target' => [
            'action' => '/share/receive',
            'method' => 'POST',
            'enctype' => 'multipart/form-data',
            'params' => [
                'title' => 'title',
                'text' => 'text',
                'url' => 'url',
                'files' => [
                    [
                        'name' => 'files',
                        'accept' => ['image/*', 'text/*', 'application/json'],
                    ],
                ],
            ],
        ],

        // App shortcuts
        'shortcuts' => [
            [
                'name' => 'Open Admin',
                'short_name' => 'Admin',
                'description' => 'Go to admin dashboard',
                'url' => '/admin',
                'icons' => [
                    [
                        'src' => '/logo.png',
                        'sizes' => '192x192',
                    ],
                ],
            ],
            [
                'name' => 'Check-ins',
                'short_name' => 'Check-ins',
                'description' => 'Recent check-ins',
                'url' => '/admin',
            ],
        ],

        // Widgets (experimental)
        'widgets' => [
            [
                'name' => 'Parking Status',
                'short_name' => 'Status',
                'description' => 'Quick view of parking occupancy',
                'url' => '/widgets/status',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Configuration
    |--------------------------------------------------------------------------
    | Toggles the application's debug mode based on the environment variable
    */

    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Livewire Integration
    |--------------------------------------------------------------------------
    | Set to true if you're using Livewire in your application to enable
    | Livewire-specific PWA optimizations or features.
    */

    'livewire-app' => false,
];
