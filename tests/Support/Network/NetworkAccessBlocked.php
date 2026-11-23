<?php

declare(strict_types=1);

namespace Tests\Support\Network;

use RuntimeException;

/**
 * A test tried to open an http(s) stream to a host other than localhost.
 */
final class NetworkAccessBlocked extends RuntimeException
{
    public static function forUrl(string $url): self {
        return new self(
            sprintf(
                'Tests may only open http(s) streams to localhost, "%s" was requested. Put the call behind a DI service '
                . '(the `http.client` service has Tests\Support\Http\FakeHttpClient) and fake it in the test.',
                $url,
            ),
        );
    }
}
