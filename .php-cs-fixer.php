<?php

declare(strict_types=1);

use ESoul\CodingStandard\Config;
use PhpCsFixer\Finder;

// The ruleset is shared by eSoul PHP projects: esoul-cz/coding-standard. Extend it with
// Config::create($finder, ...)->setRules([...Config::rules(), ...]) when the project needs to.
$finder = Finder::create()
    ->files()
    ->in([
        __DIR__ . '/src',
        __DIR__ . '/tests',
        __DIR__ . '/include',
        __DIR__ . '/config',
        __DIR__ . '/routes',
    ])
    ->append([
        __DIR__ . '/psr-worker.php',
    ])
    ->exclude([
        'vendor',
        'node_modules',
        'temp',
        'logs',
        'dist',
    ])
    ->ignoreDotFiles(false)
    ->ignoreVCS(true);

return Config::create($finder, __DIR__ . '/temp/tools/php-cs-fixer.cache');
