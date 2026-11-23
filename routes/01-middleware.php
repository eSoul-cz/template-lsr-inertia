<?php

declare(strict_types=1);

/** @var Router $this */

use App\Http\Middleware\BrowserCsrfMiddleware;
use Lsr\Core\Routing\Router;
use Lsr\Inertia\Middleware\InertiaMiddleware;

// Page routes rendered through Inertia.
$this->middlewareGroup('web', $this->serviceRef(InertiaMiddleware::class));
// Unsafe (POST/PUT/PATCH/DELETE) browser requests must be same-origin XHR/Inertia requests.
$this->middlewareGroup('csrf', $this->serviceRef(BrowserCsrfMiddleware::class));
