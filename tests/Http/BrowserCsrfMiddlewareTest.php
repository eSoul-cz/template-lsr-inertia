<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Middleware\BrowserCsrfMiddleware;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class BrowserCsrfMiddlewareTest extends TestCase
{
    private BrowserCsrfMiddleware $middleware;

    protected function setUp(): void {
        $this->middleware = new BrowserCsrfMiddleware();
    }

    #[Test]
    #[DataProvider('safeMethods')]
    public function safe_methods_pass_without_any_headers(string $method): void {
        $response = $this->process(new ServerRequest($method, 'http://app.test/'));

        self::assertSame(200, $response->getStatusCode());
    }

    #[Test]
    public function post_without_a_browser_client_header_is_rejected(): void {
        $request = new ServerRequest('POST', 'http://app.test/form', ['Origin' => 'http://app.test']);

        self::assertSame(419, $this->process($request)->getStatusCode());
    }

    #[Test]
    public function post_without_origin_or_referer_is_rejected(): void {
        $request = new ServerRequest('POST', 'http://app.test/form', ['X-Inertia' => 'true']);

        self::assertSame(419, $this->process($request)->getStatusCode());
    }

    #[Test]
    public function post_from_another_origin_is_rejected(): void {
        $request = new ServerRequest('POST', 'http://app.test/form', [
            'X-Inertia' => 'true',
            'Origin'    => 'https://evil.test',
        ]);

        self::assertSame(419, $this->process($request)->getStatusCode());
    }

    #[Test]
    public function same_origin_inertia_post_passes(): void {
        $request = new ServerRequest('POST', 'http://app.test:8001/form', [
            'X-Inertia' => 'true',
            'Origin'    => 'http://app.test:8001',
        ]);

        self::assertSame(200, $this->process($request)->getStatusCode());
    }

    #[Test]
    public function referer_is_used_when_origin_is_missing(): void {
        $request = new ServerRequest('PUT', 'http://app.test/form', [
            'X-Requested-With' => 'XMLHttpRequest',
            'Referer'          => 'http://app.test/page?x=1',
        ]);

        self::assertSame(200, $this->process($request)->getStatusCode());
    }

    #[Test]
    public function fastcgi_scheme_and_host_define_the_public_origin(): void {
        // What RoadRunner delivers for NGINX's `HTTPS on` and `HTTP_HOST example.com` FastCGI parameters.
        $request = new ServerRequest('POST', 'https://example.com/form', [
            'Host'      => 'example.com',
            'X-Inertia' => 'true',
            'Origin'    => 'https://example.com',
        ]);

        self::assertSame(200, $this->process($request)->getStatusCode());
    }

    #[Test]
    public function an_origin_with_another_scheme_than_the_request_is_rejected(): void {
        $request = new ServerRequest('POST', 'http://example.com/form', [
            'Host'      => 'example.com',
            'X-Inertia' => 'true',
            'Origin'    => 'https://example.com',
        ]);

        self::assertSame(419, $this->process($request)->getStatusCode());
    }

    #[Test]
    #[DataProvider('forgedForwardedHeaders')]
    public function forwarded_headers_do_not_define_the_public_origin(string $origin, string $host): void {
        $request = new ServerRequest('POST', 'https://app.test/form', [
            'Host'              => 'app.test',
            'X-Inertia'         => 'true',
            'Origin'            => $origin,
            'X-Forwarded-Proto' => 'https',
            'X-Forwarded-Host'  => $host,
        ]);

        self::assertSame(419, $this->process($request)->getStatusCode());
    }

    /**
     * @return iterable<string, array{string, string}>
     */
    public static function forgedForwardedHeaders(): iterable {
        yield 'the attacker host' => ['https://evil.test', 'evil.test'];
        yield 'a host the request was not made to' => ['https://example.com', 'example.com'];
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function safeMethods(): iterable {
        yield 'GET' => ['GET'];
        yield 'HEAD' => ['HEAD'];
        yield 'OPTIONS' => ['OPTIONS'];
    }

    private function process(ServerRequestInterface $request): ResponseInterface {
        $handler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface {
                return new Response(200);
            }
        };

        return $this->middleware->process($request, $handler);
    }
}
