<?php

declare(strict_types=1);

namespace Tests\Support\Http;

use LogicException;
use Nette\DI\Container;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Replaces the `http.client` service in tests: nothing leaves the process. A test queues the responses (or client
 * exceptions) it expects and reads the recorded requests afterwards. A request nobody queued a response for fails the test.
 */
final class FakeHttpClient implements ClientInterface
{
    private const string SERVICE = 'http.client';

    /** @var list<RequestInterface> */
    public private(set) array $requests = [];

    /** @var list<ResponseInterface|ClientExceptionInterface> */
    private array $queue = [];

    /** Binds a fresh fake as the container's `http.client`; call `release()` in tearDown. */
    public static function install(Container $container): self {
        $fake = new self();
        $container->removeService(self::SERVICE);
        $container->addService(self::SERVICE, $fake);

        return $fake;
    }

    /** Drops the fake; the container builds the real client again on the next request. */
    public static function release(Container $container): void {
        $container->removeService(self::SERVICE);
    }

    public function queue(ResponseInterface | ClientExceptionInterface ...$responses): self {
        foreach ($responses as $response) {
            $this->queue[] = $response;
        }

        return $this;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface {
        $this->requests[] = $request;

        $next = array_shift($this->queue);
        if ($next === null) {
            throw new LogicException(
                sprintf('FakeHttpClient has no response queued for %s %s.', $request->getMethod(), $request->getUri()),
            );
        }
        if ($next instanceof ClientExceptionInterface) {
            throw $next;
        }

        return $next;
    }
}
