<?php

declare(strict_types=1);

namespace App\Core;

use Lsr\Core\Requests\Exceptions\RouteNotFoundException;
use Lsr\Core\Requests\Request;
use Psr\Http\Message\ResponseInterface;

/**
 * Application entry point. Registered as `lsr.app` in config/di/extensions/lsr.neon.
 */
class App extends \Lsr\Core\App
{
    /**
     * @throws RouteNotFoundException
     */
    public function run(): ResponseInterface {
        $request = $this->getRequest();

        // RoadRunner serves the public assets. Core's fallback would readfile() any non-PHP file below the
        // application root (config, composer.json, ...) and exit() the worker, so a file is never a route.
        if ($request instanceof Request && $request->isStaticFile()) {
            throw new RouteNotFoundException($request);
        }

        return parent::run();
    }
}
