<?php

declare(strict_types=1);

/**
 * App configuration: constants, runtime directories and the PRODUCTION flag.
 */

use Lsr\Core\Config;

require_once ROOT . 'include/constants.php';

foreach ([TMP_DIR, TMP_DIR . 'cache/', TMP_DIR . 'latte/', LOG_DIR, UPLOAD_DIR] as $directory) {
    if ( ! is_dir($directory) && ! @mkdir($directory, 0777, true) && ! is_dir($directory)) {
        throw new \RuntimeException(sprintf('Directory "%s" was not created', $directory));
    }
}

// Debug mode: private/config.ini ([General] DEBUG) wins, otherwise the APP_DEBUG environment variable.
$config = Config::getInstance();
$debug = $config->getConfig('General')['DEBUG'] ?? $config->getConfig('ENV')['APP_DEBUG'] ?? false;

/** If in production */
define('PRODUCTION', ! filter_var($debug, FILTER_VALIDATE_BOOL));
