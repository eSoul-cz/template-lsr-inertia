<?php

declare(strict_types=1);

namespace Tests\Support\Network;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Tests\Support\Http\LocalHttpServer;

final class NetworkGuardTest extends TestCase
{
    #[Test]
    #[DataProvider('externalUrls')]
    public function http_streams_to_other_hosts_fail_the_test(string $url): void {
        $this->expectException(NetworkAccessBlocked::class);

        file_get_contents($url);
    }

    #[Test]
    public function a_stream_to_localhost_is_opened_by_the_original_wrapper(): void {
        $server = LocalHttpServer::start();
        try {
            $body = file_get_contents($server->baseUrl . '/items');
        } finally {
            $server->stop();
        }

        self::assertIsString($body);
        $echo = json_decode($body, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($echo);
        self::assertSame('/items', $echo['uri']);
    }

    #[Test]
    #[DataProvider('hosts')]
    public function recognizes_localhost_hosts(string $host, bool $local): void {
        self::assertSame($local, LocalOnlyStreamWrapper::isLocalHost($host));
    }

    /** @return iterable<string, array{string}> */
    public static function externalUrls(): iterable {
        yield 'http' => ['http://203.0.113.10/'];
        yield 'https' => ['https://example.com/'];
        yield 'localhost as a subdomain label of another host' => ['http://localhost.example.com/'];
        yield 'userinfo that looks like localhost' => ['http://127.0.0.1@example.com/'];
    }

    /** @return iterable<string, array{string, bool}> */
    public static function hosts(): iterable {
        yield 'localhost' => ['localhost', true];
        yield 'subdomain of localhost' => ['app.localhost', true];
        yield 'loopback' => ['127.0.0.1', true];
        yield 'loopback range' => ['127.1.2.3', true];
        yield 'IPv6 loopback in brackets' => ['[::1]', true];
        yield 'other host' => ['example.com', false];
        yield 'private network address' => ['10.0.0.5', false];
        yield 'not a loopback address' => ['1127.0.0.1', false];
        yield 'empty' => ['', false];
    }
}
