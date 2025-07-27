# 🏢 Kodikas Laravel Multitenant

[![Latest Stable Version](https://poser.pugx.org/kodikas/laravel-multitenant/v/stable)](https://packagist.org/packages/kodikas/laravel-multitenant)
[![Total Downloads](https://poser.pugx.org/kodikas/laravel-multitenant/downloads)](https://packagist.org/packages/kodikas/laravel-multitenant)
[![License](https://poser.pugx.org/kodikas/laravel-multitenant/license)](https://packagist.org/packages/kodikas/laravel-multitenant)
[![PHP Version Require](https://poser.pugx.org/kodikas/laravel-multitenant/require/php)](https://packagist.org/packages/kodikas/laravel-multitenant)

Paquete Laravel para manejo avanzado de multi-tenant tipo SaaS con enfoque latinoamericano/empresarial. Diseñado exclusivamente para **Laravel 12.x** y versiones futuras.

## ✨ Características Principales

- 🏢 **Multi-tenancy avanzado** con soporte empresarial
- 🔐 **Control de acceso granular** con roles y permisos
- 🌐 **Middleware empresarial** con 12+ tipos de parámetros
- 🎯 **Detección automática** de tenants
- 🔒 **Restricciones de acceso** (IP, tiempo, suscripciones)
- 🚀 **Compatible con Laravel 12.x** exclusivamente

## 📋 Requisitos del Sistema

- **PHP**: 8.2 o superior
- **Laravel**: 12.0 o superior (**EXCLUSIVAMENTE**)
- **Composer**: 2.0 o superior

## 🚀 Instalación

```bash
composer require kodikas/laravel-multitenant
```

### Publicar Configuración

```bash
php artisan vendor:publish --provider="Kodikas\Multitenant\MultitenantServiceProvider"
```

### Ejecutar Migraciones

```bash
php artisan migrate
```

## 🔧 Configuración Básica

### 1. Configurar Middleware

En `app/Http/Kernel.php`:

```php
protected $routeMiddleware = [
    // ...
    'tenant.access' => \Kodikas\Multitenant\Middleware\TenantAccessControlMiddleware::class,
    'tenant.identify' => \Kodikas\Multitenant\Middleware\IdentifyTenantMiddleware::class,
    'tenant.ensure' => \Kodikas\Multitenant\Middleware\EnsureTenantMiddleware::class,
];
```

### 2. Usar en Rutas

```php
Route::middleware(['tenant.identify', 'tenant.access:type:employee,role:admin'])
    ->group(function () {
        Route::get('/admin', [AdminController::class, 'index']);
    });
```

## 📖 Uso Avanzado

### Control de Acceso por Tipo de Usuario

```php
// Solo empleados
Route::middleware('tenant.access:type:employee')->group(function () {
    // Rutas para empleados
});

// Solo propietarios
Route::middleware('tenant.access:type:owner')->group(function () {
    // Rutas para propietarios
});
```

### Control por Roles y Permisos

```php
// Por rol específico
Route::middleware('tenant.access:role:admin')->group(function () {
    // Solo administradores
});

// Por permiso específico
Route::middleware('tenant.access:permission:manage_users')->group(function () {
    // Solo usuarios con permiso específico
});
```

### Restricciones Avanzadas

```php
// Por nivel jerárquico
Route::middleware('tenant.access:level:80')->group(function () {
    // Solo usuarios con nivel 80 o superior
});

// Por plan de suscripción
Route::middleware('tenant.access:plan:premium')->group(function () {
    // Solo usuarios con plan premium
});

// Restricción por IP
Route::middleware('tenant.access:ip:192.168.1.100')->group(function () {
    // Solo desde IP específica
});
```

## 🧪 Testing

```bash
# Ejecutar tests del paquete
cd packages/kodikas/multitenant
../../../vendor/bin/phpunit

# Tests de compatibilidad local
./test-compatibility.sh "12.*" "8.3" "prefer-stable"
```

## 📊 Compatibilidad

| Laravel | PHP 8.2 | PHP 8.3 | Estado |
|---------|---------|---------|--------|
| 12.x    | ✅      | ✅      | Soportado |
| 13.x+   | ✅      | ✅      | Futuro |

⚠️ **IMPORTANTE**: Este paquete **NO es compatible** con Laravel 10.x o 11.x.

## 📚 Documentación

- [Guía de Compatibilidad](COMPATIBILITY.md)
- [Changelog](../../CHANGELOG.md)
- [Contribuir](../../CONTRIBUTING.md)
- [Seguridad](../../SECURITY.md)

## 🤝 Contribuir

¡Las contribuciones son bienvenidas! Por favor revisa [CONTRIBUTING.md](../../CONTRIBUTING.md) para detalles.

## 📄 Licencia

Este paquete es software de código abierto licenciado bajo la [Licencia MIT](LICENSE).

## 👨‍💻 Autor

**Miguel E Uicab Canabal**
- 🌐 Website: [miguelmort.tech](https://miguelmort.tech)
- 📧 Email: miguel@kodikas.com
- 🐙 GitHub: [@MiguelMort09](https://github.com/MiguelMort09)

## 🏢 Empresa

Desarrollado por [KODIKAS Software](https://github.com/KODIKAS-Software) con enfoque en soluciones empresariales latinoamericanas.

---

⭐ Si este paquete te resulta útil, ¡no olvides darle una estrella en GitHub!
