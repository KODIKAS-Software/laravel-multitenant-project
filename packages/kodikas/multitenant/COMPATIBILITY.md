# 🔍 Laravel Multitenant - Matriz de Compatibilidad

Este documento describe la compatibilidad del paquete **Kodikas Laravel Multitenant** con diferentes versiones de Laravel y PHP.

## 📊 Matriz de Compatibilidad

| Laravel | PHP 8.2 | PHP 8.3 | Estado | Notas |
|---------|---------|---------|--------|-------|
| 10.x    | ✅      | ✅      | Soportado | LTS hasta marzo 2025 |
| 11.x    | ✅      | ✅      | Soportado | LTS hasta febrero 2026 |
| 12.x    | ✅      | ✅      | Recomendado | Versión actual |

## 🛠️ Requisitos del Sistema

### Versiones Mínimas
- **PHP**: 8.2 o superior
- **Laravel**: 10.0 o superior
- **Composer**: 2.0 o superior

### Dependencias Principales
```json
{
    "require": {
        "php": "^8.2",
        "laravel/framework": "^10.0|^11.0|^12.0",
        "spatie/laravel-permission": "^6.0",
        "doctrine/dbal": "^3.0"
    }
}
```

### Dependencias de Desarrollo
```json
{
    "require-dev": {
        "orchestra/testbench": "^8.0|^9.0|^10.0",
        "phpunit/phpunit": "^10.0|^11.0",
        "mockery/mockery": "^1.6"
    }
}
```

## 🧪 Testing de Compatibilidad

### Automático (GitHub Actions)
El paquete incluye un workflow completo de validación de compatibilidad que se ejecuta:

- **Semanalmente**: Todos los domingos a las 3 AM UTC
- **En cada push** a la rama `master` que modifique el paquete
- **En Pull Requests** hacia `master`
- **Manualmente** desde GitHub Actions

### Local
Puedes ejecutar tests de compatibilidad localmente usando los scripts incluidos:

#### Linux/macOS
```bash
# Probar Laravel 12.x con PHP 8.2
./test-compatibility.sh

# Probar versión específica
./test-compatibility.sh "11.*" "8.3" "prefer-stable"

# Probar versiones mínimas
./test-compatibility.sh "10.*" "8.2" "prefer-lowest"
```

#### Windows
```cmd
REM Probar Laravel 12.x con PHP 8.2
test-compatibility.bat

REM Probar versión específica
test-compatibility.bat "11.*" "8.3" "prefer-stable"

REM Probar versiones mínimas
test-compatibility.bat "10.*" "8.2" "prefer-lowest"
```

## 📋 Estrategias de Testing

### Prefer Stable
- Instala las versiones más recientes estables de todas las dependencias
- Recomendado para desarrollo y producción
- Detecta problemas con las últimas versiones

### Prefer Lowest
- Instala las versiones mínimas permitidas de todas las dependencias
- Asegura compatibilidad con versiones anteriores
- Detecta problemas de compatibilidad hacia atrás

## 🔄 Ciclo de Validación

### Antes de cada Release
1. **Validación automática** de todas las combinaciones de Laravel/PHP
2. **Tests de regresión** con prefer-lowest y prefer-stable
3. **Generación de reportes** de compatibilidad
4. **Actualización automática** de la documentación

### Monitoreo Continuo
- **Tests semanales** para detectar breaking changes
- **Notificaciones automáticas** en caso de incompatibilidades
- **Badges de estado** en la documentación

## 🚨 Incompatibilidades Conocidas

### PHP 8.1
- **No soportado**: El paquete requiere PHP 8.2 como mínimo
- **Razón**: Uso de características específicas de PHP 8.2+

### Laravel 9.x
- **No soportado**: Versión EOL desde agosto 2024
- **Migración**: Actualizar a Laravel 10.x LTS o superior

## 📈 Hoja de Ruta de Compatibilidad

### Próximas Versiones
- **Laravel 13.x**: Soporte planificado para Q2 2025
- **PHP 8.4**: Evaluación en progreso
- **PHP 9.0**: Seguimiento del desarrollo

### Política de Soporte
- **Versiones LTS**: Soporte hasta el EOL oficial de Laravel
- **Versiones no-LTS**: Soporte por 18 meses desde el lanzamiento
- **PHP**: Soporte por 2 años desde la versión mínima

## 🔧 Configuración Avanzada

### Variables de Entorno para Testing
```bash
# Para tests de compatibilidad
export LARAVEL_VERSION="12.*"
export PHP_VERSION="8.3"
export DEPENDENCY_STRATEGY="prefer-stable"

# Para debugging
export PHPUNIT_DEBUG=true
export COMPOSER_MEMORY_LIMIT=-1
```

### Configuración Personalizada de PHPUnit
Cada versión de Laravel requiere configuraciones específicas de PHPUnit:

- **Laravel 10.x**: PHPUnit 10.x
- **Laravel 11.x**: PHPUnit 10.5+
- **Laravel 12.x**: PHPUnit 11.x

## 📞 Soporte

### Reportar Incompatibilidades
Si encuentras problemas de compatibilidad:

1. **Verifica** que estés usando versiones soportadas
2. **Ejecuta** los tests locales de compatibilidad
3. **Crea un issue** con:
   - Versiones de PHP y Laravel
   - Logs completos del error
   - Pasos para reproducir
   - Resultado del script de compatibilidad

### Solicitar Soporte para Nueva Versión
Para solicitar soporte de nuevas versiones de Laravel o PHP:

1. **Abre un issue** con la etiqueta `enhancement`
2. **Incluye** justificación y cronograma
3. **Considera** contribuir con un PR

---

**Última actualización**: Automática via GitHub Actions
**Próxima validación**: Cada domingo a las 3 AM UTC
