<?php

/*
 * PHPStan bootstrap file for multitenant package
 */

// Include the Composer autoloader
require_once __DIR__ . '/../../../vendor/autoload.php';

// Create a minimal Laravel application for PHPStan
$app = new \Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__, 4)
);

// Register service providers needed for PHPStan analysis
$app->singleton(
    \Illuminate\Contracts\Http\Kernel::class,
    \App\Http\Kernel::class
);

$app->singleton(
    \Illuminate\Contracts\Console\Kernel::class,
    \App\Console\Kernel::class
);

$app->singleton(
    \Illuminate\Contracts\Debug\ExceptionHandler::class,
    \App\Exceptions\Handler::class
);

// Bootstrap the application
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Register the multitenant service provider
$app->register(\Kodikas\Multitenant\MultitenantServiceProvider::class);
