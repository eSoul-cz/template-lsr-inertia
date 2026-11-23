<?php

declare(strict_types=1);

namespace App\Http\Client;

use CurlHandle;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * The application's outgoing HTTP client (PSR-18), registered as the `http.client` service.
 *
 * Code that calls an external system depends on `Psr\Http\Client\ClientInterface`, never on this class, so the tests can
 * replace the service with `Tests\Support\Http\FakeHttpClient`. Redirects are not followed and only http(s) is allowed;
 * an HTTP error status is a normal response, a failed connection is an `HttpClientException`.
 */
final readonly class CurlHttpClient implements ClientInterface
{
    public function __construct(
        private ResponseFactoryInterface $responseFactory,
        private StreamFactoryInterface $streamFactory,
        private int $timeoutSeconds = 10,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface {
        $url = (string) $request->getUri();
        $method = $request->getMethod();
        if ($url === '' || $method === '') {
            throw new HttpClientException($request, 'The request needs a method and an absolute URL.');
        }

        $handle = curl_init();
        if ($handle === false) {
            throw new HttpClientException($request, 'Cannot initialize cURL.');
        }

        $statusCode = 0;
        $reason = '';
        /** @var array<string, list<string>> $responseHeaders */
        $responseHeaders = [];

        $requestHeaders = ['Expect:'];
        foreach ($request->getHeaders() as $name => $values) {
            $requestHeaders[] = $name . ': ' . implode(', ', $values);
        }

        $options = [
            CURLOPT_URL            => $url,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $requestHeaders,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_PROTOCOLS_STR  => 'http,https',
            CURLOPT_CONNECTTIMEOUT => $this->timeoutSeconds,
            CURLOPT_TIMEOUT        => $this->timeoutSeconds,
            CURLOPT_HEADERFUNCTION => static function (
                CurlHandle $handle,
                string $line,
            ) use (&$statusCode, &$reason, &$responseHeaders): int {
                if (preg_match('~^HTTP/\S+\s+(\d{3})\s*(.*?)\r?\n?$~', $line, $status) === 1) {
                    // A new status line (the final response after `100 Continue`, for example) starts a new header block.
                    $statusCode = (int) $status[1];
                    $reason = $status[2];
                    $responseHeaders = [];
                } elseif (str_contains($line, ':')) {
                    [$name, $value] = explode(':', $line, 2);
                    $responseHeaders[trim($name)][] = trim($value);
                }

                return strlen($line);
            },
        ];

        if ($request->getMethod() === 'HEAD') {
            $options[CURLOPT_NOBODY] = true;
        } else {
            $body = (string) $request->getBody();
            if ($body !== '') {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
        }

        curl_setopt_array($handle, $options);
        $result = curl_exec($handle);
        if ( ! is_string($result)) {
            throw new HttpClientException($request, curl_error($handle), curl_errno($handle));
        }

        $response = $this->responseFactory->createResponse($statusCode, $reason);
        foreach ($responseHeaders as $name => $values) {
            $response = $response->withAddedHeader($name, $values);
        }

        return $response->withBody($this->streamFactory->createStream($result));
    }
}
