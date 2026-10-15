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
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    | Gateway de pagamentos das assinaturas do SaaS (checkout transparente:
    | cartão criptografado no navegador + Pix com QR Code).
    |
    | `public_key` é opcional: quando vazia, é buscada na conta e cacheada.
    | `sandbox` alterna entre as bases de teste e de produção.
    */
    'pagbank' => [
        'token' => env('PAGBANK_TOKEN'),
        'public_key' => env('PAGBANK_PUBLIC_KEY'),
        'sandbox' => env('PAGBANK_SANDBOX', true),
        'base_url' => env('PAGBANK_SANDBOX', true)
            ? 'https://sandbox.api.pagseguro.com'
            : 'https://api.pagseguro.com',
        'webhook_url' => env('PAGBANK_WEBHOOK_URL'),
    ],

];
