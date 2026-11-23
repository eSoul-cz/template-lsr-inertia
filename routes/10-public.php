<?php

declare(strict_types=1);

/** @var Router $this */

use App\Http\Controllers\Health;
use App\Http\Controllers\Home;
use Lsr\Core\Routing\Router;

// Probe route: deliberately outside every middleware group (no Inertia, no CSRF).
$this->get('/up', [Health::class, 'show'])->name('health');

$routes = $this->group('/')->middlewareAll('web');

$routes->get('/', [Home::class, 'show'])->name('home');
