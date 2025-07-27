# Kodikas Laravel Multitenant Package

Un paquete Laravel avanzado para manejo de multi-tenant (multi inquilino) tipo SaaS, con enfoque latinoamericano/empresarial.

*An advanced Laravel package for multi-tenant SaaS management with Latin American/enterprise focus.*

## 🚀 Características / Features

### Español
- **Aislamiento de datos real**: Soporte para estrategias de base de datos única, múltiples bases de datos o esquemas
- **Gestión avanzada de usuarios**: Diferentes tipos de usuario (empleados, clientes, proveedores, socios)
- **Control de acceso granular**: Sistema de roles y permisos por tenant
- **Switch automático de tenant**: Por subdominio, dominio, path, header o sesión
- **Migraciones independientes**: Migraciones y seeders por tenant
- **Integración de billing**: Soporte para Stripe, Conekta y otros proveedores
- **Panel administrativo**: Gestión completa de tenants y usuarios
- **Sistema de eventos**: Hooks para ciclo de vida del tenant
- **Restricciones de acceso**: Control por IP, horarios, días de la semana
- **Cache inteligente**: Optimización de consultas con cache por tenant

### English
- **Real data isolation**: Support for single database, multiple databases or schema strategies
- **Advanced user management**: Different user types (employees, clients, vendors, partners)
- **Granular access control**: Role and permission system per tenant
- **Automatic tenant switching**: By subdomain, domain, path, header or session
- **Independent migrations**: Migrations and seeders per tenant
- **Billing integration**: Support for Stripe, Conekta and other providers
- **Admin panel**: Complete tenant and user management
- **Event system**: Hooks for tenant lifecycle
- **Access restrictions**: Control by IP, schedules, days of the week
- **Smart caching**: Query optimization with tenant-specific cache

## 📋 Requisitos / Requirements

- PHP 8.2+
- Laravel 10.x, 11.x, 12.x
- MySQL 8.0+ / PostgreSQL 13+ / SQLite 3.x

## 🛠 Instalación / Installation

```bash
composer require kodikas/laravel-multitenant
```

### Publicar configuración / Publish configuration

```bash
php artisan vendor:publish --tag=multitenant-config
php artisan vendor:publish --tag=multitenant-migrations
```

### Ejecutar migraciones / Run migrations

```bash
php artisan migrate
```

## ⚙️ Configuración / Configuration

### Configuración básica / Basic configuration

```php
// config/multitenant.php

return [
    // Método de identificación del tenant
    // Tenant identification method
    'identification_method' => 'subdomain', // subdomain, domain, path, header, session
    
    // Estrategia de base de datos
    // Database strategy
    'database_strategy' => 'single_database', // single_database, multiple_databases, multiple_schemas
    
    // Configuración de billing
    // Billing configuration
    'billing' => [
        'enabled' => true,
        'provider' => 'stripe', // stripe, conekta
        'plans' => [
            'basic' => [
                'name' => 'Plan Básico',
                'price' => 990, // centavos/cents
                'currency' => 'MXN',
                'features' => [
                    'users' => 5,
                    'storage' => '1GB',
                    'api_calls' => 1000,
                ],
            ],
        ],
    ],
];
```

### Actualizar modelo User / Update User model

```php
use Kodikas\Multitenant\Traits\HasTenants;

class User extends Authenticatable
{
    use HasTenants;
    
    // ...existing code...
}
```

## 🎯 Uso Básico / Basic Usage

### Crear un tenant / Create a tenant

```bash
php artisan tenant:make "Mi Empresa" --slug=mi-empresa --plan=basic --owner-email=admin@miempresa.com
```

### Obtener tenant actual / Get current tenant

```php
use Kodikas\Multitenant\Facades\Tenant;

$tenant = Tenant::current();
$tenantName = $tenant->name;
$tenantPlan = $tenant->plan;
```

### Verificar acceso de usuario / Check user access

```php
$user = auth()->user();
$tenant = Tenant::current();

// Verificar si el usuario pertenece al tenant
// Check if user belongs to tenant
if ($user->belongsToTenant($tenant)) {
    // Obtener tipo de usuario en el tenant
    // Get user type in tenant
    $userType = $user->getUserTypeInTenant($tenant);
    
    // Verificar permisos específicos
    // Check specific permissions
    if ($user->hasPermissionInTenant($tenant, 'manage_users')) {
        // Usuario puede gestionar otros usuarios
        // User can manage other users
    }
}
```

### Ejecutar en contexto de tenant / Execute in tenant context

```php
$tenant = Tenant::find(1);

$tenant->run(function () {
    // Este código se ejecuta en el contexto del tenant
    // This code runs in tenant context
    $users = User::all(); // Solo usuarios del tenant actual
});
```

## 🏗 Tipos de Usuario / User Types

### Tipos disponibles / Available types

| Tipo / Type | Descripción / Description |
|-------------|---------------------------|
| `owner` | Propietario del tenant / Tenant owner |
| `admin` | Administrador / Administrator |
| `employee` | Empleado / Employee |
| `client` | Cliente / Client |
| `vendor` | Proveedor / Vendor |
| `partner` | Socio / Partner |
| `consultant` | Consultor / Consultant |
| `guest` | Invitado / Guest |

### Roles disponibles / Available roles

| Rol / Role | Descripción / Description |
|------------|---------------------------|
| `super_admin` | Super administrador / Super administrator |
| `admin` | Administrador / Administrator |
| `manager` | Gerente / Manager |
| `employee` | Empleado / Employee |
| `client` | Cliente / Client |
| `viewer` | Observador / Viewer |

## 🛡 Control de Acceso / Access Control

### Middleware disponible / Available middleware

```php
// Identificar tenant automáticamente
// Automatically identify tenant
Route::middleware('tenant.identify')->group(function () {
    // Rutas que necesitan identificación de tenant
    // Routes that need tenant identification
});

// Asegurar que existe un tenant válido
// Ensure valid tenant exists
Route::middleware('tenant.ensure')->group(function () {
    // Rutas que requieren tenant válido
    // Routes that require valid tenant
});

// Control de acceso avanzado
// Advanced access control
Route::middleware('tenant.access:type:employee,permission:manage_users')->group(function () {
    // Solo empleados con permiso de gestión de usuarios
    // Only employees with user management permission
});
```

### Parámetros de middleware / Middleware parameters

| Parámetro / Parameter | Descripción / Description |
|----------------------|---------------------------|
| `type:employee` | Solo usuarios de tipo empleado / Only employee type users |
| `role:admin` | Solo usuarios con rol admin / Only users with admin role |
| `permission:manage_users` | Solo usuarios con permiso específico / Only users with specific permission |
| `level:80` | Nivel mínimo de jerarquía / Minimum hierarchy level |
| `not_client` | Excluir clientes / Exclude clients |
| `internal_only` | Solo usuarios internos / Only internal users |
| `subscription_required` | Requiere suscripción activa / Requires active subscription |
| `plan:pro` | Requiere plan específico / Requires specific plan |

## 🔧 Comandos Artisan / Artisan Commands

### Gestión de tenants / Tenant management

```bash
# Crear tenant
# Create tenant
php artisan tenant:make "Nombre del Tenant" --slug=tenant-slug

# Migrar tenants
# Migrate tenants
php artisan tenant:migrate
php artisan tenant:migrate tenant-slug --fresh --seed

# Hacer seed de tenants
# Seed tenants
php artisan tenant:seed
php artisan tenant:seed tenant-slug --class=CustomSeeder
```

## 📊 Modelos y Relaciones / Models and Relationships

### Modelo Tenant

```php
use Kodikas\Multitenant\Models\Tenant;

$tenant = Tenant::create([
    'name' => 'Mi Empresa',
    'slug' => 'mi-empresa',
    'domain' => 'miempresa.com',
    'plan' => 'basic',
    'settings' => [
        'app_name' => 'Mi App',
        'locale' => 'es',
        'timezone' => 'America/Mexico_City',
    ],
]);

// Verificar límites
// Check limits
if ($tenant->canPerform('users', $currentUserCount)) {
    // Puede agregar más usuarios
    // Can add more users
}

// Verificar suscripción
// Check subscription
if ($tenant->subscriptionActive()) {
    // Suscripción activa
    // Active subscription
}
```

### Relación TenantUser

```php
use Kodikas\Multitenant\Models\TenantUser;

$tenantUser = TenantUser::create([
    'tenant_id' => $tenant->id,
    'user_id' => $user->id,
    'user_type' => TenantUser::TYPE_EMPLOYEE,
    'role' => TenantUser::ROLE_MANAGER,
    'permissions' => ['view_reports', 'manage_clients'],
    'access_restrictions' => [
        'allowed_ips' => ['192.168.1.100'],
        'access_hours' => ['start' => 9, 'end' => 18],
        'access_days' => [1, 2, 3, 4, 5], // Lunes a viernes
    ],
]);

// Verificar acceso
// Check access
if ($tenantUser->canAccess()) {
    // Usuario puede acceder
    // User can access
}

// Verificar permisos
// Check permissions
if ($tenantUser->hasPermission('manage_clients')) {
    // Usuario puede gestionar clientes
    // User can manage clients
}
```

## 🎨 Traits Disponibles / Available Traits

### HasTenants

Agrega funcionalidad de tenants al modelo User.
*Adds tenant functionality to User model.*

```php
// Obtener tenants del usuario
// Get user's tenants
$tenants = $user->tenants;
$activeTenants = $user->activeTenants;
$ownedTenants = $user->ownedTenants;

// Unirse a tenant
// Join tenant
$user->joinTenant($tenant, 'employee', 'manager', ['view_reports']);

// Abandonar tenant
// Leave tenant
$user->leaveTenant($tenant);
```

### BelongsToTenant

Filtro automático por tenant para modelos.
*Automatic tenant filtering for models.*

```php
use Kodikas\Multitenant\Traits\BelongsToTenant;

class Product extends Model
{
    use BelongsToTenant;
    
    // Automáticamente filtrado por tenant actual
    // Automatically filtered by current tenant
}

// Consultas sin filtro de tenant
// Queries without tenant filter
$allProducts = Product::withoutTenant()->get();

// Consultas para tenant específico
// Queries for specific tenant
$tenantProducts = Product::forTenant($tenantId)->get();
```

### HasUserTypeAccess

Control de acceso basado en tipo de usuario.
*User type based access control.*

```php
use Kodikas\Multitenant\Traits\HasUserTypeAccess;

class Order extends Model
{
    use BelongsToTenant, HasUserTypeAccess;
    
    // Automáticamente filtrado según tipo de usuario
    // Automatically filtered by user type
}

// Verificar si el usuario actual puede ver este modelo
// Check if current user can view this model
if ($order->canBeViewedByCurrentUser()) {
    // Usuario puede ver esta orden
    // User can view this order
}
```

## 🌐 Estrategias de Base de Datos / Database Strategies

### Base de datos única / Single database

```php
'database_strategy' => 'single_database',
```

Todos los tenants comparten la misma base de datos con campo `tenant_id`.
*All tenants share the same database with `tenant_id` field.*

### Múltiples bases de datos / Multiple databases

```php
'database_strategy' => 'multiple_databases',
```

Cada tenant tiene su propia base de datos.
*Each tenant has its own database.*

### Múltiples esquemas / Multiple schemas (PostgreSQL)

```php
'database_strategy' => 'multiple_schemas',
```

Cada tenant tiene su propio esquema en PostgreSQL.
*Each tenant has its own schema in PostgreSQL.*

## 🔔 Sistema de Eventos / Event System

### Eventos disponibles / Available events

```php
use Kodikas\Multitenant\Events\TenantCreated;
use Kodikas\Multitenant\Events\TenantUpdated;
use Kodikas\Multitenant\Events\TenantDeleted;
use Kodikas\Multitenant\Events\UserInvited;
use Kodikas\Multitenant\Events\SubscriptionUpdated;

// Escuchar eventos
// Listen to events
Event::listen(TenantCreated::class, function ($event) {
    $tenant = $event->tenant;
    // Lógica personalizada al crear tenant
    // Custom logic when creating tenant
});
```

## 🧪 Testing

```php
use Kodikas\Multitenant\Models\Tenant;
use Kodikas\Multitenant\Facades\Tenant as TenantFacade;

class TenantTest extends TestCase
{
    public function test_tenant_creation()
    {
        $tenant = Tenant::create([
            'name' => 'Test Tenant',
            'slug' => 'test-tenant',
        ]);
        
        TenantFacade::set($tenant);
        
        $this->assertEquals($tenant->id, TenantFacade::current()->id);
    }
    
    public function test_user_access_control()
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create();
        
        $user->joinTenant($tenant, 'client', 'client', ['view_own_data']);
        
        $this->assertTrue($user->canAccessTenant($tenant));
        $this->assertTrue($user->hasPermissionInTenant($tenant, 'view_own_data'));
        $this->assertFalse($user->hasPermissionInTenant($tenant, 'manage_users'));
    }
}
```

## 📚 API Reference

### TenantManager

| Método / Method | Parámetros / Parameters | Retorno / Return | Descripción / Description |
|-----------------|-------------------------|------------------|---------------------------|
| `current()` | - | `?Tenant` | Obtiene el tenant actual / Gets current tenant |
| `set($tenant)` | `?Tenant $tenant` | `void` | Establece el tenant actual / Sets current tenant |
| `check()` | - | `bool` | Verifica si hay tenant actual / Checks if there's current tenant |
| `run($tenant, $callback)` | `Tenant $tenant, callable $callback` | `mixed` | Ejecuta callback en contexto de tenant / Executes callback in tenant context |
| `find($identifier)` | `string $identifier` | `?Tenant` | Busca tenant por identificador / Finds tenant by identifier |
| `create($data)` | `array $data` | `Tenant` | Crea nuevo tenant / Creates new tenant |
| `delete($tenant)` | `Tenant $tenant` | `bool` | Elimina tenant / Deletes tenant |

### TenantUser

| Método / Method | Parámetros / Parameters | Retorno / Return | Descripción / Description |
|-----------------|-------------------------|------------------|---------------------------|
| `canAccess()` | - | `bool` | Verifica si puede acceder / Checks if can access |
| `hasPermission($permission)` | `string $permission` | `bool` | Verifica permiso específico / Checks specific permission |
| `canPerform($action, $context)` | `string $action, array $context` | `bool` | Verifica si puede realizar acción / Checks if can perform action |
| `isOwner()` | - | `bool` | Verifica si es propietario / Checks if is owner |
| `isAdmin()` | - | `bool` | Verifica si es administrador / Checks if is admin |
| `isEmployee()` | - | `bool` | Verifica si es empleado / Checks if is employee |
| `isClient()` | - | `bool` | Verifica si es cliente / Checks if is client |

## 🤝 Contribuciones / Contributing

Las contribuciones son bienvenidas. Por favor, sigue estos pasos:
*Contributions are welcome. Please follow these steps:*

1. Fork el repositorio / Fork the repository
2. Crea una rama feature / Create a feature branch
3. Realiza tus cambios / Make your changes
4. Escribe tests / Write tests
5. Envía un pull request / Submit a pull request

## 📄 Licencia / License

MIT License. Ver archivo LICENSE para más detalles.
*MIT License. See LICENSE file for more details.*

## 🆘 Soporte / Support

Para soporte técnico, por favor crea un issue en GitHub.
*For technical support, please create an issue on GitHub.*

## 🔗 Enlaces / Links

- [Documentación completa / Full documentation](https://github.com/kodikas/laravel-multitenant/wiki)
- [Ejemplos / Examples](https://github.com/kodikas/laravel-multitenant/tree/main/examples)
- [Changelog](https://github.com/kodikas/laravel-multitenant/blob/main/CHANGELOG.md)
