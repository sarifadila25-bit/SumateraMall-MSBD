<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'fuseki' => [
        'query_url' => env('FUSEKI_URL', 'http://localhost:3030/sumateramall/query'),
        'update_url' => env('FUSEKI_UPDATE_URL', 'http://localhost:3030/sumateramall/update'),
    ],
    'osm' => [
        'overpass_url' => env('OSM_OVERPASS_URL', 'https://overpass-api.de/api/interpreter'),
        'osrm_url' => env('OSRM_URL', 'https://router.project-osrm.org'),
        'user_agent' => env('OSM_USER_AGENT', 'SumateraMallSemanticExplorer/1.0'),
    ],
    'google_maps' => [
        'browser_key' => env('GOOGLE_MAPS_BROWSER_KEY'),
        'server_key' => env('GOOGLE_MAPS_SERVER_KEY'),
        'routes_url' => env('GOOGLE_MAPS_ROUTES_URL', 'https://routes.googleapis.com/directions/v2:computeRoutes'),
    ],
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'model' => env('GEMINI_MODEL', 'gemini-2.5-flash'),
        'endpoint' => env('GEMINI_API_ENDPOINT', 'https://generativelanguage.googleapis.com/v1beta/models'),
    ],
    'budget' => [
        'price_level_estimates' => [
            1 => (int) env('BUDGET_PRICE_LEVEL_1', 30000),
            2 => (int) env('BUDGET_PRICE_LEVEL_2', 75000),
            3 => (int) env('BUDGET_PRICE_LEVEL_3', 150000),
            4 => (int) env('BUDGET_PRICE_LEVEL_4', 300000),
        ],
    ],

];
