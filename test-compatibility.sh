#!/bin/bash

# Script para ejecutar tests de compatibilidad localmente
# Usage: ./test-compatibility.sh [laravel-version] [php-version] [strategy]

set -e

# Colores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuración por defecto
DEFAULT_LARAVEL="12.*"
DEFAULT_PHP="8.2"
DEFAULT_STRATEGY="prefer-stable"

# Parámetros
LARAVEL_VERSION=${1:-$DEFAULT_LARAVEL}
PHP_VERSION=${2:-$DEFAULT_PHP}
DEPENDENCY_STRATEGY=${3:-$DEFAULT_STRATEGY}

echo -e "${BLUE}🔍 Laravel Multitenant - Local Compatibility Test${NC}"
echo -e "${BLUE}=================================================${NC}"
echo -e "Laravel Version: ${YELLOW}$LARAVEL_VERSION${NC}"
echo -e "PHP Version: ${YELLOW}$PHP_VERSION${NC}"
echo -e "Strategy: ${YELLOW}$DEPENDENCY_STRATEGY${NC}"
echo ""

# Verificar que PHP esté disponible
if ! command -v php &> /dev/null; then
    echo -e "${RED}❌ PHP no está instalado o no está en PATH${NC}"
    exit 1
fi

# Verificar versión de PHP
CURRENT_PHP=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
if [[ "$CURRENT_PHP" != "$PHP_VERSION" ]]; then
    echo -e "${YELLOW}⚠️  Advertencia: PHP actual ($CURRENT_PHP) difiere de la versión solicitada ($PHP_VERSION)${NC}"
fi

# Crear directorio temporal
TEST_DIR="./tmp/compatibility-test-$(date +%s)"
mkdir -p "$TEST_DIR"

echo -e "${BLUE}📁 Creando entorno de prueba en: $TEST_DIR${NC}"

# Crear composer.json específico
cat > "$TEST_DIR/composer.json" << EOF
{
    "name": "kodikas/compatibility-test",
    "type": "project",
    "require": {
        "php": "^$PHP_VERSION",
        "laravel/framework": "$LARAVEL_VERSION",
        "spatie/laravel-permission": "^6.0",
        "doctrine/dbal": "^3.0"
    },
    "require-dev": {
        "orchestra/testbench": "$(case $LARAVEL_VERSION in 10.*) echo '^8.0' ;; 11.*) echo '^9.0' ;; *) echo '^10.0' ;; esac)",
        "phpunit/phpunit": "$(case $LARAVEL_VERSION in 10.*) echo '^10.0' ;; 11.*) echo '^10.5' ;; *) echo '^11.0' ;; esac)",
        "mockery/mockery": "^1.6"
    },
    "autoload": {
        "psr-4": {
            "Kodikas\\\\Multitenant\\\\": "packages/kodikas/multitenant/src/"
        }
    },
    "autoload-dev": {
        "psr-4": {
            "Kodikas\\\\Multitenant\\\\Tests\\\\": "packages/kodikas/multitenant/tests/"
        }
    },
    "minimum-stability": "dev",
    "prefer-stable": true,
    "repositories": [
        {
            "type": "path",
            "url": "./packages/kodikas/multitenant"
        }
    ]
}
EOF

# Copiar el paquete
echo -e "${BLUE}📦 Copiando paquete multitenant...${NC}"
mkdir -p "$TEST_DIR/packages/kodikas"
cp -r "./packages/kodikas/multitenant" "$TEST_DIR/packages/kodikas/"

# Instalar dependencias
echo -e "${BLUE}⬇️  Instalando dependencias con estrategia: $DEPENDENCY_STRATEGY${NC}"
cd "$TEST_DIR"

if [[ "$DEPENDENCY_STRATEGY" == "prefer-lowest" ]]; then
    composer update --prefer-lowest --prefer-dist --no-interaction --no-progress
else
    composer update --prefer-stable --prefer-dist --no-interaction --no-progress
fi

# Verificar instalación
echo -e "${BLUE}✅ Verificando versiones instaladas:${NC}"
composer show laravel/framework
composer show orchestra/testbench

# Crear configuración PHPUnit específica
PHPUNIT_SCHEMA=$(case $LARAVEL_VERSION in 10.*|11.*) echo "10.5" ;; *) echo "11.5" ;; esac)

cat > "packages/kodikas/multitenant/phpunit-local.xml" << EOF
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/$PHPUNIT_SCHEMA/phpunit.xsd"
         bootstrap="../../../vendor/autoload.php"
         colors="true"
         processIsolation="false"
         stopOnFailure="false"
         executionOrder="random"
         failOnWarning="true"
         failOnRisky="true"
         failOnEmptyTestSuite="true"
         beStrictAboutOutputDuringTests="true"
         cacheDirectory=".phpunit.cache">
    <testsuites>
        <testsuite name="Local Compatibility Tests - Laravel $LARAVEL_VERSION">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>src</directory>
        </include>
        <exclude>
            <directory>src/database</directory>
        </exclude>
    </source>
    <coverage>
        <report>
            <clover outputFile="coverage-local.xml"/>
            <html outputDirectory="coverage-html"/>
        </report>
    </coverage>
</phpunit>
EOF

# Ejecutar tests
echo -e "${BLUE}🧪 Ejecutando tests de compatibilidad...${NC}"
cd "packages/kodikas/multitenant"

if ../../../vendor/bin/phpunit --configuration=phpunit-local.xml --testdox; then
    echo -e "${GREEN}✅ ¡Todos los tests pasaron exitosamente!${NC}"
    echo -e "${GREEN}✅ Laravel $LARAVEL_VERSION es compatible con PHP $PHP_VERSION${NC}"
    RESULT=0
else
    echo -e "${RED}❌ Algunos tests fallaron${NC}"
    echo -e "${RED}❌ Posible incompatibilidad detectada${NC}"
    RESULT=1
fi

# Generar reporte
cd ../../../
echo -e "${BLUE}📊 Generando reporte de compatibilidad...${NC}"

cat > "compatibility-report-local.md" << EOF
# Reporte de Compatibilidad Local

## Configuración
- **Laravel**: $LARAVEL_VERSION
- **PHP**: $PHP_VERSION (actual: $CURRENT_PHP)
- **Estrategia de dependencias**: $DEPENDENCY_STRATEGY
- **Fecha**: $(date)

## Resultado
$(if [[ $RESULT -eq 0 ]]; then echo "✅ **COMPATIBLE** - Todos los tests pasaron"; else echo "❌ **INCOMPATIBLE** - Algunos tests fallaron"; fi)

## Versiones Instaladas
\`\`\`
$(composer show --direct)
\`\`\`

## Archivos Generados
- Configuración PHPUnit: packages/kodikas/multitenant/phpunit-local.xml
- Coverage: packages/kodikas/multitenant/coverage-local.xml
- Coverage HTML: packages/kodikas/multitenant/coverage-html/
EOF

echo -e "${BLUE}📄 Reporte guardado en: compatibility-report-local.md${NC}"

# Cleanup opcional
echo ""
read -p "¿Deseas limpiar el directorio temporal? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    cd ..
    rm -rf "$TEST_DIR"
    echo -e "${GREEN}🧹 Directorio temporal limpiado${NC}"
fi

exit $RESULT
