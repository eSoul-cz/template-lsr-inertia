<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Http\Health\DatabaseCheck;
use Lsr\Core\Requests\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * Liveness and readiness probe for the container health check and the reverse proxy: `GET /up`.
 * Returns no application data, only the status of the dependencies the application cannot work without.
 */
final class Health extends Controller
{
    public function __construct(private readonly DatabaseCheck $database) {
    }

    public function show(): ResponseInterface {
        $ok = $this->database->isAvailable();

        return Response::create($ok ? 200 : 503, ['Cache-Control' => 'no-store'])
            ->withJsonBody(['status' => $ok ? 'ok' : 'unavailable']);
    }
}
