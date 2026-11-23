<?php

declare(strict_types=1);

/**
 * All basic global constants. Requires ROOT to be defined.
 */

/** Directory containing log files */
const LOG_DIR = ROOT . 'logs/';
/** Directory containing temporary files and caches */
const TMP_DIR = ROOT . 'temp/';
/** Directory containing Latte templates */
const TEMPLATE_DIR = ROOT . 'templates/';
/** Directory for files hidden from the user (local config overrides) */
const PRIVATE_DIR = ROOT . 'private/';
const LANGUAGE_DIR = ROOT . 'languages/';
const LANGUAGE_FILE_NAME = 'LAC';
const ASSETS_DIR = ROOT . 'assets/';
/** App's default language */
const DEFAULT_LANGUAGE = 'cs';
/**
 * The application clock (PHP default time zone and database session time zone). The business time zone is named in
 * CONTEXT.md and applied only where a date is shown or a calendar boundary is cut.
 */
const APP_TIMEZONE = 'UTC';

/** Directory for user uploads; configurable by UPLOAD_DIR (absolute, or relative to ROOT) */
$configuredUploadDir = $_ENV['UPLOAD_DIR'] ?? $_SERVER['UPLOAD_DIR'] ?? '';
$uploadDir = is_string($configuredUploadDir) && $configuredUploadDir !== '' ? $configuredUploadDir : ROOT . 'upload/';
if ($uploadDir[0] !== '/') {
    $uploadDir = ROOT . $uploadDir;
}
define('UPLOAD_DIR', rtrim($uploadDir, '/') . '/');
