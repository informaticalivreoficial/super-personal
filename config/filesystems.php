<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Todo o produto usa o disco **padrão** para mídia (fotos, logos, favicon,
    | documentos): `Storage::disk()->...`. Para trocar o armazenamento basta
    | mudar `FILESYSTEM_DISK` no .env:
    |
    |   public → storage/app/public (disco local, dev/testes)
    |   s3     → Cloudflare R2 (produção)
    |
    */

    'default' => env('FILESYSTEM_DISK', 'public'),

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
         * Mídia local (dev/testes): servida pelo symlink público `/storage`
         * criado por `storage:link`.
         */
        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        /*
         * Cloudflare R2 (S3-compatível). Ativado com `FILESYSTEM_DISK=s3`.
         * O prefixo `R2_ROOT` faz parte da URL pública (`R2_URL`).
         */
        's3' => [
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
            // Credencial/rede ruim deve estourar, não falhar em silêncio.
            'throw' => true,
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
