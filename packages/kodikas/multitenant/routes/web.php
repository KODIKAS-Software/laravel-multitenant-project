<?php

use Illuminate\Support\Facades\Route;
use Kodikas\Multitenant\Http\Controllers\TenantAdminController;
use Kodikas\Multitenant\Http\Controllers\TenantInvitationController;

/*
|--------------------------------------------------------------------------
| Tenant Management Routes
|--------------------------------------------------------------------------
*/

// Rutas para invitaciones públicas (no requieren autenticación)
Route::group(['prefix' => 'tenant/invitation'], function () {
    Route::get('accept/{token}', [TenantInvitationController::class, 'showAcceptForm'])
        ->name('tenant.invitation.accept');
    Route::post('accept/{token}', [TenantInvitationController::class, 'acceptInvitation'])
        ->name('tenant.invitation.process');
});

// Rutas protegidas para administración de tenants
Route::group([
    'prefix' => 'tenant/admin',
    'middleware' => ['auth', 'tenant.identify', 'tenant.ensure']
], function () {

    // Dashboard principal
    Route::get('/', [TenantAdminController::class, 'dashboard'])
        ->middleware('tenant.access:permission:view_dashboard')
        ->name('tenant.admin.dashboard');

    // Gestión de usuarios
    Route::group(['prefix' => 'users'], function () {
        Route::get('/', [TenantAdminController::class, 'users'])
            ->middleware('tenant.access:permission:view_users')
            ->name('tenant.admin.users');

        Route::get('invite', [TenantAdminController::class, 'inviteUser'])
            ->middleware('tenant.access:permission:invite_users')
            ->name('tenant.admin.users.invite');

        Route::post('invite', [TenantAdminController::class, 'sendInvitation'])
            ->middleware('tenant.access:permission:invite_users')
            ->name('tenant.admin.users.send-invitation');

        Route::put('{user}', [TenantAdminController::class, 'updateUser'])
            ->middleware('tenant.access:permission:manage_users')
            ->name('tenant.admin.users.update');

        Route::delete('{user}', [TenantAdminController::class, 'removeUser'])
            ->middleware('tenant.access:permission:manage_users')
            ->name('tenant.admin.users.remove');
    });

    // Configuración del tenant
    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [TenantAdminController::class, 'settings'])
            ->middleware('tenant.access:permission:manage_tenant')
            ->name('tenant.admin.settings');

        Route::put('/', [TenantAdminController::class, 'updateSettings'])
            ->middleware('tenant.access:permission:manage_tenant')
            ->name('tenant.admin.settings.update');
    });

    // Facturación (solo para propietarios y admins)
    Route::group(['prefix' => 'billing'], function () {
        Route::get('/', [TenantAdminController::class, 'billing'])
            ->middleware('tenant.access:type:owner,type:admin')
            ->name('tenant.admin.billing');
    });

    // Logs de acceso
    Route::get('access-logs', [TenantAdminController::class, 'accessLogs'])
        ->middleware('tenant.access:permission:view_logs')
        ->name('tenant.admin.access-logs');

    // Analytics
    Route::get('analytics', [TenantAdminController::class, 'analytics'])
        ->middleware('tenant.access:permission:view_analytics')
        ->name('tenant.admin.analytics');
});

// Rutas específicas por tipo de usuario
Route::group([
    'middleware' => ['auth', 'tenant.identify', 'tenant.ensure']
], function () {

    // Panel para empleados
    Route::group([
        'prefix' => 'employee',
        'middleware' => 'tenant.access:type:employee'
    ], function () {
        Route::get('dashboard', function () {
            return view('multitenant::employee.dashboard');
        })->name('tenant.employee.dashboard');
    });

    // Portal para clientes
    Route::group([
        'prefix' => 'client',
        'middleware' => 'tenant.access:type:client'
    ], function () {
        Route::get('dashboard', function () {
            return view('multitenant::client.dashboard');
        })->name('tenant.client.dashboard');

        Route::get('orders', function () {
            // Solo pueden ver sus propios pedidos
            return view('multitenant::client.orders');
        })->name('tenant.client.orders');
    });

    // Portal para proveedores
    Route::group([
        'prefix' => 'vendor',
        'middleware' => 'tenant.access:type:vendor'
    ], function () {
        Route::get('dashboard', function () {
            return view('multitenant::vendor.dashboard');
        })->name('tenant.vendor.dashboard');

        Route::get('products', function () {
            // Solo pueden ver sus propios productos
            return view('multitenant::vendor.products');
        })->name('tenant.vendor.products');
    });

    // Portal para socios
    Route::group([
        'prefix' => 'partner',
        'middleware' => 'tenant.access:type:partner'
    ], function () {
        Route::get('dashboard', function () {
            return view('multitenant::partner.dashboard');
        })->name('tenant.partner.dashboard');
    });
});

// API Routes para diferentes tipos de usuario
Route::group([
    'prefix' => 'api/tenant',
    'middleware' => ['auth:sanctum', 'tenant.identify', 'tenant.ensure']
], function () {

    // API para clientes
    Route::group([
        'prefix' => 'client',
        'middleware' => 'tenant.access:type:client'
    ], function () {
        Route::get('profile', function () {
            $user = auth()->user();
            $tenant = app('tenant')->current();
            return response()->json([
                'user' => $user,
                'tenant_info' => $user->getTenantAccessStats($tenant)
            ]);
        });

        Route::get('orders', function () {
            // Implementar lógica para obtener pedidos del cliente
            return response()->json(['orders' => []]);
        });
    });

    // API para empleados
    Route::group([
        'prefix' => 'employee',
        'middleware' => 'tenant.access:type:employee'
    ], function () {
        Route::get('dashboard-stats', function () {
            $user = auth()->user();
            $tenant = app('tenant')->current();
            $tenantUser = $user->getTenantUser($tenant);

            $stats = [];

            if ($tenantUser->hasPermission('view_client_stats')) {
                $stats['total_clients'] = $tenant->users()
                    ->wherePivot('user_type', 'client')
                    ->count();
            }

            if ($tenantUser->hasPermission('view_order_stats')) {
                // Agregar estadísticas de pedidos
                $stats['pending_orders'] = 0; // Implementar
            }

            return response()->json($stats);
        });
    });

    // API para proveedores
    Route::group([
        'prefix' => 'vendor',
        'middleware' => 'tenant.access:type:vendor'
    ], function () {
        Route::get('products', function () {
            // Solo productos del proveedor actual
            return response()->json(['products' => []]);
        });

        Route::post('products', function () {
            $tenant = app('tenant')->current();
            $user = auth()->user();
            $tenantUser = $user->getTenantUser($tenant);

            // Verificar límites de productos
            if (!$tenantUser->canPerform('create_product', ['current_products' => 0])) {
                return response()->json([
                    'error' => 'Límite de productos alcanzado'
                ], 403);
            }

            // Crear producto
            return response()->json(['message' => 'Producto creado']);
        });
    });
});
