<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Tenant Identification Method
    |--------------------------------------------------------------------------
    |
    | Métodos disponibles para identificar el tenant:
    | 'subdomain' - Por subdominio (app1.miapp.com)
    | 'domain' - Por dominio completo (cliente1.com)
    | 'path' - Por path (/tenant1/dashboard)
    | 'header' - Por header HTTP (X-Tenant-ID)
    | 'session' - Por variable de sesión
    |
    */
    'identification_method' => env('TENANT_IDENTIFICATION_METHOD', 'subdomain'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Strategy
    |--------------------------------------------------------------------------
    |
    | Estrategias de aislamiento de datos:
    | 'single_database' - Una BD con campo tenant_id
    | 'multiple_databases' - Una BD por tenant
    | 'multiple_schemas' - Un esquema por tenant (PostgreSQL)
    |
    */
    'database_strategy' => env('TENANT_DATABASE_STRATEGY', 'single_database'),

    /*
    |--------------------------------------------------------------------------
    | Central Database Connection
    |--------------------------------------------------------------------------
    |
    | Conexión de base de datos central donde se almacenan los tenants
    |
    */
    'central_connection' => env('DB_CONNECTION', 'mysql'),

    /*
    |--------------------------------------------------------------------------
    | Tenant Database Configuration
    |--------------------------------------------------------------------------
    */
    'tenant_database' => [
        'prefix' => env('TENANT_DB_PREFIX', 'tenant_'),
        'suffix' => env('TENANT_DB_SUFFIX', ''),
        'connection_template' => [
            'driver' => env('DB_CONNECTION', 'mysql'),
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '3306'),
            'username' => env('DB_USERNAME', 'forge'),
            'password' => env('DB_PASSWORD', ''),
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tenant Model
    |--------------------------------------------------------------------------
    */
    'tenant_model' => \Kodikas\Multitenant\Models\Tenant::class,

    /*
    |--------------------------------------------------------------------------
    | User Model
    |--------------------------------------------------------------------------
    */
    'user_model' => \App\Models\User::class,

    /*
    |--------------------------------------------------------------------------
    | Central Domain
    |--------------------------------------------------------------------------
    */
    'central_domain' => env('CENTRAL_DOMAIN', 'localhost'),

    /*
    |--------------------------------------------------------------------------
    | Billing Configuration
    |--------------------------------------------------------------------------
    */
    'billing' => [
        'enabled' => env('TENANT_BILLING_ENABLED', false),
        'provider' => env('BILLING_PROVIDER', 'stripe'),
        'plans' => [
            'basic' => [
                'name' => 'Plan Básico',
                'price' => 990,
                'currency' => 'MXN',
                'features' => [
                    'users' => 5,
                    'storage' => '1GB',
                    'api_calls' => 1000,
                ],
            ],
            'pro' => [
                'name' => 'Plan Profesional',
                'price' => 2990,
                'currency' => 'MXN',
                'features' => [
                    'users' => 25,
                    'storage' => '10GB',
                    'api_calls' => 10000,
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */
    'cache' => [
        'enabled' => env('TENANT_CACHE_ENABLED', true),
        'ttl' => env('TENANT_CACHE_TTL', 3600),
        'prefix' => 'tenant:',
    ],
];
