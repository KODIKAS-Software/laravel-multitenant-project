@echo off
setlocal enabledelayedexpansion

REM Script para ejecutar tests de compatibilidad localmente en Windows
REM Usage: test-compatibility.bat [laravel-version] [php-version] [strategy]

set "DEFAULT_LARAVEL=12.*"
set "DEFAULT_PHP=8.2"
set "DEFAULT_STRATEGY=prefer-stable"

REM Parámetros
set "LARAVEL_VERSION=%~1"
set "PHP_VERSION=%~2"
set "DEPENDENCY_STRATEGY=%~3"

if "%LARAVEL_VERSION%"=="" set "LARAVEL_VERSION=%DEFAULT_LARAVEL%"
if "%PHP_VERSION%"=="" set "PHP_VERSION=%DEFAULT_PHP%"
if "%DEPENDENCY_STRATEGY%"=="" set "DEPENDENCY_STRATEGY=%DEFAULT_STRATEGY%"

echo.
echo 🔍 Laravel Multitenant - Local Compatibility Test
echo =================================================
echo Laravel Version: %LARAVEL_VERSION%
echo PHP Version: %PHP_VERSION%
echo Strategy: %DEPENDENCY_STRATEGY%
echo.

REM Verificar que PHP esté disponible
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP no está instalado o no está en PATH
    exit /b 1
)

REM Crear directorio temporal
for /f "tokens=* delims=" %%i in ('powershell -command "[DateTimeOffset]::Now.ToUnixTimeSeconds()"') do set timestamp=%%i
set "TEST_DIR=tmp\compatibility-test-!timestamp!"
mkdir "%TEST_DIR%" 2>nul

echo 📁 Creando entorno de prueba en: %TEST_DIR%

REM Determinar versión de testbench y phpunit
if "%LARAVEL_VERSION%"=="10.*" (
    set "TESTBENCH=^8.0"
    set "PHPUNIT=^10.0"
    set "PHPUNIT_SCHEMA=10.5"
) else if "%LARAVEL_VERSION%"=="11.*" (
    set "TESTBENCH=^9.0"
    set "PHPUNIT=^10.5"
    set "PHPUNIT_SCHEMA=10.5"
) else (
    set "TESTBENCH=^10.0"
    set "PHPUNIT=^11.0"
    set "PHPUNIT_SCHEMA=11.5"
)

REM Crear composer.json específico
(
echo {
echo     "name": "kodikas/compatibility-test",
echo     "type": "project",
echo     "require": {
echo         "php": "^%PHP_VERSION%",
echo         "laravel/framework": "%LARAVEL_VERSION%",
echo         "spatie/laravel-permission": "^6.0",
echo         "doctrine/dbal": "^3.0"
echo     },
echo     "require-dev": {
echo         "orchestra/testbench": "%TESTBENCH%",
echo         "phpunit/phpunit": "%PHPUNIT%",
echo         "mockery/mockery": "^1.6"
echo     },
echo     "autoload": {
echo         "psr-4": {
echo             "Kodikas\\Multitenant\\": "packages/kodikas/multitenant/src/"
echo         }
echo     },
echo     "autoload-dev": {
echo         "psr-4": {
echo             "Kodikas\\Multitenant\\Tests\\": "packages/kodikas/multitenant/tests/"
echo         }
echo     },
echo     "minimum-stability": "dev",
echo     "prefer-stable": true,
echo     "repositories": [
echo         {
echo             "type": "path",
echo             "url": "./packages/kodikas/multitenant"
echo         }
echo     ]
echo }
) > "%TEST_DIR%\composer.json"

REM Copiar el paquete
echo 📦 Copiando paquete multitenant...
mkdir "%TEST_DIR%\packages\kodikas" 2>nul
xcopy "packages\kodikas\multitenant" "%TEST_DIR%\packages\kodikas\multitenant" /E /I /Q

REM Instalar dependencias
echo ⬇️  Instalando dependencias con estrategia: %DEPENDENCY_STRATEGY%
cd "%TEST_DIR%"

if "%DEPENDENCY_STRATEGY%"=="prefer-lowest" (
    composer update --prefer-lowest --prefer-dist --no-interaction --no-progress
) else (
    composer update --prefer-stable --prefer-dist --no-interaction --no-progress
)

REM Verificar instalación
echo ✅ Verificando versiones instaladas:
composer show laravel/framework
composer show orchestra/testbench

REM Crear configuración PHPUnit específica
(
echo ^<?xml version="1.0" encoding="UTF-8"?^>
echo ^<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
echo          xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/%PHPUNIT_SCHEMA%/phpunit.xsd"
echo          bootstrap="../../../vendor/autoload.php"
echo          colors="true"
echo          processIsolation="false"
echo          stopOnFailure="false"
echo          executionOrder="random"
echo          failOnWarning="true"
echo          failOnRisky="true"
echo          failOnEmptyTestSuite="true"
echo          beStrictAboutOutputDuringTests="true"
echo          cacheDirectory=".phpunit.cache"^>
echo     ^<testsuites^>
echo         ^<testsuite name="Local Compatibility Tests - Laravel %LARAVEL_VERSION%"^>
echo             ^<directory^>tests^</directory^>
echo         ^</testsuite^>
echo     ^</testsuites^>
echo     ^<source^>
echo         ^<include^>
echo             ^<directory^>src^</directory^>
echo         ^</include^>
echo         ^<exclude^>
echo             ^<directory^>src/database^</directory^>
echo         ^</exclude^>
echo     ^</source^>
echo     ^<coverage^>
echo         ^<report^>
echo             ^<clover outputFile="coverage-local.xml"/^>
echo             ^<html outputDirectory="coverage-html"/^>
echo         ^</report^>
echo     ^</coverage^>
echo ^</phpunit^>
) > "packages\kodikas\multitenant\phpunit-local.xml"

REM Ejecutar tests
echo 🧪 Ejecutando tests de compatibilidad...
cd "packages\kodikas\multitenant"

..\..\..\vendor\bin\phpunit --configuration=phpunit-local.xml --testdox
if errorlevel 1 (
    echo ❌ Algunos tests fallaron
    echo ❌ Posible incompatibilidad detectada
    set "RESULT=1"
) else (
    echo ✅ ¡Todos los tests pasaron exitosamente!
    echo ✅ Laravel %LARAVEL_VERSION% es compatible con PHP %PHP_VERSION%
    set "RESULT=0"
)

REM Generar reporte
cd ..\..\..\
echo 📊 Generando reporte de compatibilidad...

(
echo # Reporte de Compatibilidad Local
echo.
echo ## Configuración
echo - **Laravel**: %LARAVEL_VERSION%
echo - **PHP**: %PHP_VERSION%
echo - **Estrategia de dependencias**: %DEPENDENCY_STRATEGY%
echo - **Fecha**: %DATE% %TIME%
echo.
echo ## Resultado
if "%RESULT%"=="0" (
    echo ✅ **COMPATIBLE** - Todos los tests pasaron
) else (
    echo ❌ **INCOMPATIBLE** - Algunos tests fallaron
)
echo.
echo ## Archivos Generados
echo - Configuración PHPUnit: packages/kodikas/multitenant/phpunit-local.xml
echo - Coverage: packages/kodikas/multitenant/coverage-local.xml
echo - Coverage HTML: packages/kodikas/multitenant/coverage-html/
) > "compatibility-report-local.md"

echo 📄 Reporte guardado en: compatibility-report-local.md

echo.
set /p cleanup="¿Deseas limpiar el directorio temporal? (y/N): "
if /i "%cleanup%"=="y" (
    cd ..
    rmdir /s /q "%TEST_DIR%"
    echo 🧹 Directorio temporal limpiado
)

exit /b %RESULT%
