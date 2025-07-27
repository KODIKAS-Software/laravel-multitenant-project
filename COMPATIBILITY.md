# 🔍 Laravel Multitenant - Matriz de Compatibilidad

Este documento describe la compatibilidad del paquete **Kodikas Laravel Multitenant** con Laravel 12.x y versiones futuras.

## 📊 Matriz de Compatibilidad

| Laravel | PHP 8.2 | PHP 8.3 | Estado | Notas |
|---------|---------|---------|--------|-------|
| 12.x    | ✅      | ✅      | Recomendado | Versión mínima soportada |
| 13.x+   | ✅      | ✅      | Futuro | Soporte planificado |

## 🛠️ Requisitos del Sistema

### Versiones Mínimas
- **PHP**: 8.2 o superior
- **Laravel**: 12.0 o superior (EXCLUSIVAMENTE)
- **Composer**: 2.0 o superior

### Dependencias Principales
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^12.0",
        "spatie/laravel-permission": "^6.0",
        "doctrine/dbal": "^3.0"
    }
}
```

### Dependencias de Desarrollo
```json
{
    "require-dev": {
        "orchestra/testbench": "^10.0",
        "phpunit/phpunit": "^11.0",
        "mockery/mockery": "^1.6"
    }
}
```

## ⚠️ Versiones NO Soportadas

### Laravel 10.x y 11.x
- **❌ NO SOPORTADO**: Este paquete requiere Laravel 12.x como mínimo
- **Razón**: Aprovecha características específicas de Laravel 12.x
- **Migración**: Actualizar a Laravel 12.x para usar este paquete

### PHP 8.1
- **❌ NO SOPORTADO**: El paquete requiere PHP 8.2 como mínimo
- **Razón**: Uso de características específicas de PHP 8.2+

## 🧪 Testing de Compatibilidad

### Automático (GitHub Actions)
El paquete incluye validación automática solo para Laravel 12.x:

- **Semanalmente**: Todos los domingos a las 3 AM UTC
- **En cada push** a la rama `master` que modifique el paquete
- **En Pull Requests** hacia `master`
- **Manualmente** desde GitHub Actions

### Local
Puedes ejecutar tests de compatibilidad localmente:

#### Linux/macOS
```bash
# Probar Laravel 12.x con PHP 8.2 (por defecto)
./test-compatibility.sh

# Probar versión específica
./test-compatibility.sh "12.*" "8.3" "prefer-stable"

# Probar versiones mínimas
./test-compatibility.sh "12.*" "8.2" "prefer-lowest"
```

#### Windows
```cmd
REM Probar Laravel 12.x con PHP 8.2 (por defecto)
test-compatibility.bat

REM Probar versión específica
test-compatibility.bat "12.*" "8.3" "prefer-stable"

REM Probar versiones mínimas
test-compatibility.bat "12.*" "8.2" "prefer-lowest"
```

## 📈 Hoja de Ruta de Compatibilidad

### Próximas Versiones
- **Laravel 13.x**: Soporte garantizado cuando esté disponible
- **PHP 8.4**: Evaluación en progreso
- **PHP 9.0**: Seguimiento del desarrollo

### Política de Soporte
- **Laravel 12.x+**: Soporte completo garantizado
- **Versiones anteriores**: No soportadas
- **PHP**: Requiere 8.2+ exclusivamente

## 🚨 Migración desde Versiones Anteriores

Si tienes un proyecto con Laravel 10.x o 11.x:

1. **Actualiza Laravel** a 12.x primero
2. **Verifica compatibilidad** de tu aplicación
3. **Instala el paquete** después de la actualización

```bash
# Actualizar Laravel primero
composer update laravel/framework:^12.0

# Luego instalar el paquete
composer require kodikas/laravel-multitenant
```

---

**⚠️ IMPORTANTE**: Este paquete está diseñado exclusivamente para Laravel 12.x y versiones futuras. No funcionará con versiones anteriores de Laravel.
