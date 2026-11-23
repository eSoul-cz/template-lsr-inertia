<?php

declare(strict_types=1);

namespace App\Http\Client;

use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Http\Message\RequestInterface;
use RuntimeException;

/**
 * The request could not be completed (DNS, connection, TLS or timeout). An HTTP error status is not an exception.
 */
final class HttpClientException extends RuntimeException implements NetworkExceptionInterface
{
    public function __construct(private readonly RequestInterface $request, string $message, int $code = 0) {
        parent::__construct($message, $code);
    }

    public function getRequest(): RequestInterface {
        return $this->request;
    }
}
