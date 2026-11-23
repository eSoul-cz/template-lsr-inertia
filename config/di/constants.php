<?php

declare(strict_types=1);

/**
 * Constants that need to be imported into the DI container.
 */

if ( ! defined('ROOT')) {
    define('ROOT', dirname(__DIR__, 2) . '/');
}

require_once ROOT . 'include/constants.php';

return [
    'parameters' => [
        'constants' => [
            'debug'      => ! PRODUCTION,
            'production' => PRODUCTION,
            'appDir'     => ROOT,
            'logDir'     => LOG_DIR,
            'tempDir'    => TMP_DIR,
            'uploadDir'  => UPLOAD_DIR,
        ],
    ],
];
