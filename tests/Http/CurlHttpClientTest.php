<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Client\CurlHttpClient;
use App\Http\Client\HttpClientException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Http\LocalHttpServer;

#[CoversClass(CurlHttpClient::class)]
final class CurlHttpClientTest extends TestCase
{
    private LocalHttpServer $server;
    private CurlHttpClient $client;

    protected function setUp(): void {
        $this->server = LocalHttpServer::start();
        $factory = new Psr17Factory();
        $this->client = new CurlHttpClient($factory, $factory, 5);
    }

    protected function tearDown(): void {
        $this->server->stop();
    }

    #[Test]
    public function sends_the_method_headers_and_body_and_returns_the_response(): void {
        $request = new Request(
            'POST',
            $this->server->baseUrl . '/items?page=2',
            ['X-Custom' => 'one', 'Content-Type' => 'text/plain'],
            'payload',
        );

        $response = $this->client->sendRequest($request);

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('yes', $response->getHeaderLine('X-Fixture'));
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        $echo = json_decode($response->getBody()->__toString(), true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($echo);
        self::assertSame('POST', $echo['method']);
        self::assertSame('/items?page=2', $echo['uri']);
        self::assertSame('payload', $echo['body']);
        self::assertIsArray($echo['headers']);
        self::assertSame('one', $echo['headers']['X-Custom']);
    }

    #[Test]
    public function an_error_status_is_a_response_not_an_exception(): void {
        $response = $this->client->sendRequest(new Request('GET', $this->server->baseUrl . '/missing'));

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function a_failed_connection_throws_a_client_exception_with_the_request(): void {
        $request = new Request('GET', 'http://127.0.0.1:' . LocalHttpServer::freePort() . '/');

        try {
            $this->client->sendRequest($request);
            self::fail('The connection to a closed port must fail.');
        } catch (HttpClientException $exception) {
            self::assertSame($request, $exception->getRequest());
        }
    }
}
