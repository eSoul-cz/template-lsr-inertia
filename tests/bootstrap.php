<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__) . '/');
const PRODUCTION = false;

// Unit tests never open a database connection.
$_ENV['noDb'] = '1';

require_once ROOT . 'vendor/autoload.php';
require_once ROOT . 'include/constants.php';
