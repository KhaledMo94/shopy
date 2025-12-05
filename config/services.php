<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],
    'fawry' => [
        'merchant_code' => env('FAWRY_MERCHANT_CODE'),
        'secure_key'    => env('FAWRY_SECURE_KEY'),
        'mode'          => env('FAWRY_MODE', 'sandbox'),
        'sandbox_url'   => 'https://atfawry.fawrystaging.com/ECommerceWeb/Fawry/payments',
        'live_url'      => 'https://www.atfawry.com/ECommerceWeb/Fawry/payments',
    ],


];
