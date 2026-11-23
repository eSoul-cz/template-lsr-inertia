<?php

declare(strict_types=1);

/**
 * List of all DI container definition files.
 */

$services = [
    ROOT . 'vendor/lsr/logging/services.neon',
    ROOT . 'config/di/constants.php',
    ROOT . 'config/di/environment.php',
];
$services[] = PRODUCTION ? ROOT . 'config/di/services.neon' : ROOT . 'config/di/servicesDebug.neon';

// Machine-specific overrides, never committed.
if (file_exists(ROOT . 'config/di/services.local.neon')) {
    $services[] = ROOT . 'config/di/services.local.neon';
}

return $services;
