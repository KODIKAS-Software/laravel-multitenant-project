<?php

return [
    'database' => [
        'strategy' => env('MULTITENANT_DB_STRATEGY', 'single'),
        'prefix' => env('MULTITENANT_DB_PREFIX', 'tenant_'),
        'connection' => env('MULTITENANT_DB_CONNECTION', 'mysql'),
    ],

    'identification' => [
        'methods' => ['subdomain', 'domain', 'header'],
        'subdomain' => [
            'domain' => env('MULTITENANT_DOMAIN', 'localhost'),
        ],
        'header' => [
            'name' => 'X-Tenant-ID',
        ],
    ],

    'billing' => [
        'plans' => [
            'basic' => [
                'name' => 'Básico',
                'limits' => [
                    'users' => 5,
                    'storage' => 1024, // MB
                    'api_calls' => 1000,
                ],
            ],
            'pro' => [
                'name' => 'Profesional',
                'limits' => [
                    'users' => 50,
                    'storage' => 10240, // MB
                    'api_calls' => 10000,
                ],
            ],
            'enterprise' => [
                'name' => 'Empresarial',
                'limits' => [
                    'users' => -1, // unlimited
                    'storage' => -1, // unlimited
                    'api_calls' => -1, // unlimited
                ],
            ],
        ],
    ],

    'user_types' => [
        'owner' => 'Propietario',
        'admin' => 'Administrador',
        'employee' => 'Empleado',
        'client' => 'Cliente',
        'vendor' => 'Proveedor',
        'partner' => 'Socio',
        'consultant' => 'Consultor',
        'guest' => 'Invitado',
    ],

    'roles' => [
        'super_admin' => 'Super Administrador',
        'admin' => 'Administrador',
        'manager' => 'Gerente',
        'employee' => 'Empleado',
        'client' => 'Cliente',
        'viewer' => 'Observador',
    ],

    'cache' => [
        'enabled' => env('MULTITENANT_CACHE_ENABLED', true),
        'ttl' => env('MULTITENANT_CACHE_TTL', 3600),
        'prefix' => 'multitenant:',
    ],
];
