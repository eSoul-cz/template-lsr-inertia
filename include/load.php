<?php

declare(strict_types=1);

/**
 * Main bootstrap: autoloader, configuration, Tracy and the DI container.
 */

use App\Core\App;
use App\Core\Loader;
use Lsr\Core\Tracy\RoutingTracyPanel;
use Lsr\Helpers\Tools\Timer;
use Lsr\Helpers\Tracy\TimerTracyPanel;
use Tracy\Debugger;
use Tracy\NativeSession;

if ( ! defined('ROOT')) {
    define('ROOT', dirname(__DIR__) . '/');
}

require_once ROOT . 'include/constants.php';

date_default_timezone_set(APP_TIMEZONE);

// Autoload libraries
require_once ROOT . 'vendor/autoload.php';

// Load all globals and constants
require_once ROOT . 'include/config.php';

Timer::start('core.init');

// Enable tracy
Debugger::$editor = 'phpstorm://open?file=%file&line=%line';
Debugger::$dumpTheme = 'dark';
Debugger::setSessionStorage(new NativeSession());

// Register custom tracy panels
Debugger::getBar()
    ->addPanel(new TimerTracyPanel())
    ->addPanel(new RoutingTracyPanel());

Loader::init();

define('CHECK_TRANSLATIONS', (bool) (App::getInstance()->config->getConfig()['General']['TRANSLATIONS'] ?? false));
define(
    'TRANSLATIONS_COMMENTS',
    (bool) (App::getInstance()->config->getConfig()['General']['TRANSLATIONS_COMMENTS'] ?? false),
);

Timer::stop('core.init');
