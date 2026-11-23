<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Lsr\Core\Requests\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CSRF protection for unsafe methods: the request must come from the app's own browser client
 * (Inertia or XHR) and its Origin (or Referer) must match the public origin of the request.
 *
 * The public origin is the scheme and host of the request itself (the request URI and the `Host` header). Behind NGINX over
 * FastCGI they come from the `HTTPS` and `HTTP_HOST` FastCGI parameters; `X-Forwarded-*` headers are ignored.
 */
readonly class BrowserCsrfMiddleware implements MiddlewareInterface
{
    /** @var list<string> */
    private const array SAFE_METHODS = ['GET', 'HEAD', 'OPTIONS'];

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface {
        if (in_array(strtoupper($request->getMethod()), self::SAFE_METHODS, true)) {
            return $handler->handle($request);
        }

        if ( ! $this->hasBrowserClientHeader($request) || ! $this->hasSameOrigin($request)) {
            return Response::create(419)->withJsonBody([
                'message' => 'The request could not be verified. Reload the page and try again.',
            ]);
        }

        return $handler->handle($request);
    }

    private function hasBrowserClientHeader(ServerRequestInterface $request): bool {
        return strtolower($request->getHeaderLine('X-Inertia')) === 'true'
            || strtolower($request->getHeaderLine('X-Requested-With')) === 'xmlhttprequest';
    }

    private function hasSameOrigin(ServerRequestInterface $request): bool {
        $source = $request->getHeaderLine('Origin');
        if ($source === '') {
            $source = $request->getHeaderLine('Referer');
        }
        if ($source === '') {
            return false;
        }

        $parts = parse_url($source);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        // The request as the web server delivered it: over FastCGI, NGINX passes the scheme (`HTTPS`) and the host
        // (`HTTP_HOST`) as FastCGI parameters, and RoadRunner builds the request URI and the `Host` header from them.
        // `X-Forwarded-*` is not read: a client could forge it whenever the proxy does not replace it.
        $uri = $request->getUri();
        $scheme = $uri->getScheme();
        $authority = trim($request->getHeaderLine('Host'));
        if ($authority === '') {
            $authority = $uri->getAuthority();
        }

        $sourceAuthority = $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');

        return $this->origin($parts['scheme'], $sourceAuthority) === $this->origin($scheme, $authority);
    }

    private function origin(string $scheme, string $authority): string {
        $scheme = strtolower($scheme);
        $authority = strtolower($authority);
        $defaultPort = match ($scheme) {
            'http'  => ':80',
            'https' => ':443',
            default => '',
        };
        if ($defaultPort !== '' && str_ends_with($authority, $defaultPort)) {
            $authority = substr($authority, 0, -strlen($defaultPort));
        }

        return $scheme . '://' . $authority;
    }
}
