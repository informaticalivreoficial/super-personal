<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been set up for each driver as an example of the required values.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'throw' => false,
        ],

        /*
         * Disco de mídia do produto (fotos, logos, favicon, metaimg...).
         *
         * O código usa sempre `Storage::disk('public')`; o driver é trocado
         * pelo env para que o mesmo nome funcione local (dev/testes) e no
         * Cloudflare R2 (produção, driver `s3`). Assim não há refatoração dos
         * componentes nem dos testes (`Storage::fake('public')` segue igual).
         *
         * Para usar o R2: FILESYSTEM_PUBLIC_DRIVER=s3 + R2_* no .env.
         */
        'public' => env('FILESYSTEM_PUBLIC_DRIVER', 'local') === 's3'
            ? [
                'driver' => 's3',
                'key' => env('R2_ACCESS_KEY_ID'),
                'secret' => env('R2_SECRET_ACCESS_KEY'),
                'region' => env('R2_REGION', 'auto'),
                'bucket' => env('R2_BUCKET'),
                'root' => env('R2_ROOT', ''),
                'url' => env('R2_URL'),
                'endpoint' => env('R2_ENDPOINT')
                    ?: 'https://'.env('R2_ACCOUNT_ID').'.r2.cloudflarestorage.com',
                'use_path_style_endpoint' => env('R2_USE_PATH_STYLE_ENDPOINT', false),
                'visibility' => 'public',
                // Erros de credencial/rede devem estourar, não falhar em silêncio.
                'throw' => true,
            ]
            : [
                'driver' => 'local',
                'root' => storage_path('app/public'),
                'url' => env('APP_URL').'/storage',
                'visibility' => 'public',
                'throw' => false,
            ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw' => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
