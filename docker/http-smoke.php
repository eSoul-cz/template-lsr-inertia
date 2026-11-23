<?php

declare(strict_types=1);

// Runs against the built production image over HTTP and FastCGI, without bootstrapping the application in this process.
$baseUrl = rtrim(getenv('HTTP_SMOKE_BASE_URL') ?: 'http://127.0.0.1:8001', '/');
$client = curl_init();
if ($client === false) {
    fwrite(STDERR, "Cannot initialize the HTTP smoke client.\n");
    exit(1);
}

curl_setopt_array($client, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_CONNECTTIMEOUT => 2,
    CURLOPT_TIMEOUT => 4,
    CURLOPT_FOLLOWLOCATION => false,
]);

/** @param list<string> $headers @return array{status: int, headers: array<string, string>, body: string} */
function request(CurlHandle $client, string $url, array $headers = []): array
{
    $responseHeaders = [];
    curl_setopt_array($client, [
        CURLOPT_URL => $url,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_HEADERFUNCTION => static function (CurlHandle $client, string $line) use (&$responseHeaders): int {
            $separator = strpos($line, ':');
            if ($separator !== false) {
                $responseHeaders[strtolower(trim(substr($line, 0, $separator)))] = trim(substr($line, $separator + 1));
            }
            return strlen($line);
        },
    ]);
    $body = curl_exec($client);
    if (!is_string($body)) {
        throw new RuntimeException($url . ': ' . curl_error($client));
    }

    return [
        'status' => curl_getinfo($client, CURLINFO_RESPONSE_CODE),
        'headers' => $responseHeaders,
        'body' => $body,
    ];
}

function requireCondition(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}

/**
 * One FastCGI request (responder role) over a fresh connection, like NGINX sends it.
 *
 * @param array<string, string> $headers FastCGI parameters added to the defaults (HTTP_* ones are request headers)
 * @return array{status: int, headers: array<string, string>, body: string}
 */
function fastcgiRequest(string $address, string $uri, array $headers = []): array
{
    $socket = @stream_socket_client($address, $errorCode, $errorMessage, 2);
    if ($socket === false) {
        throw new RuntimeException('FastCGI ' . $address . ': ' . $errorMessage);
    }
    stream_set_timeout($socket, 4);

    $record = static fn (int $type, string $content): string => pack('CCnnCx', 1, $type, 1, strlen($content), 0) . $content;
    $length = static fn (int $size): string => $size < 128 ? chr($size) : pack('N', $size | 0x80000000);

    $params = [
        'REQUEST_METHOD'  => 'GET',
        'REQUEST_URI'     => $uri,
        'QUERY_STRING'    => '',
        'SERVER_PROTOCOL' => 'HTTP/1.1',
        'SCRIPT_FILENAME' => '/app/index.php',
        'REMOTE_ADDR'     => '127.0.0.1',
        'REMOTE_PORT'     => '50000',
        'HTTP_HOST'       => 'localhost',
    ];
    $encodedParams = '';
    foreach ([...$params, ...$headers] as $name => $value) {
        $encodedParams .= $length(strlen($name)) . $length(strlen($value)) . $name . $value;
    }

    fwrite($socket, $record(1, pack('nCx5', 1, 0)) . $record(4, $encodedParams) . $record(4, '') . $record(5, ''));

    $read = static function (int $bytes) use ($socket): string {
        $data = '';
        while (strlen($data) < $bytes) {
            $chunk = fread($socket, $bytes - strlen($data));
            if ($chunk === false || $chunk === '') {
                throw new RuntimeException('FastCGI connection closed before the response ended.');
            }
            $data .= $chunk;
        }
        return $data;
    };

    $stdout = '';
    do {
        $header = unpack('Cversion/Ctype/nrequestId/ncontentLength/CpaddingLength', $read(8));
        if ($header === false) {
            throw new RuntimeException('Invalid FastCGI record header.');
        }
        $content = $read($header['contentLength']);
        $read($header['paddingLength']);
        if ($header['type'] === 6) {
            $stdout .= $content;
        }
    } while ($header['type'] !== 3);
    fclose($socket);

    [$rawHeaders, $body] = array_pad(explode("\r\n\r\n", $stdout, 2), 2, '');
    $status = 200;
    $responseHeaders = [];
    foreach (explode("\r\n", $rawHeaders) as $line) {
        $separator = strpos($line, ':');
        if ($separator === false) {
            continue;
        }
        $name = strtolower(trim(substr($line, 0, $separator)));
        $value = trim(substr($line, $separator + 1));
        if ($name === 'status') {
            $status = (int) $value;
            continue;
        }
        $responseHeaders[$name] = $value;
    }

    return ['status' => $status, 'headers' => $responseHeaders, 'body' => $body];
}

try {
    $health = request($client, $baseUrl . '/up');
    requireCondition($health['status'] === 200, '/up: health check returned HTTP ' . $health['status']);
    $healthBody = json_decode($health['body'], true, flags: JSON_THROW_ON_ERROR);
    requireCondition(is_array($healthBody) && ($healthBody['status'] ?? null) === 'ok', '/up: unexpected health payload');
    echo "/up: health check OK\n";

    $html = request($client, $baseUrl . '/', ['Accept: text/html']);
    requireCondition($html['status'] === 200, '/: HTML returned HTTP ' . $html['status']);
    requireCondition(str_contains($html['headers']['content-type'] ?? '', 'text/html'), '/: response is not HTML');
    requireCondition(
        preg_match('~<script\b(?=[^>]*\bdata-page="app")(?=[^>]*\btype="application/json")[^>]*>(.*?)</script>~s', $html['body'], $pageMatch) === 1,
        '/: missing Inertia JSON page script',
    );
    $page = json_decode($pageMatch[1], true, flags: JSON_THROW_ON_ERROR);
    requireCondition(is_array($page) && ($page['component'] ?? null) === 'Home', '/: unexpected Inertia component');
    requireCondition(str_contains($html['body'], '<div id="app"></div>'), '/: missing the Inertia mount point');
    echo "/: HTML and Inertia page OK\n";

    $inertia = request($client, $baseUrl . '/', ['Accept: text/html, application/xhtml+xml', 'X-Inertia: true', 'X-Requested-With: XMLHttpRequest']);
    requireCondition($inertia['status'] === 200, '/: Inertia returned HTTP ' . $inertia['status']);
    requireCondition(($inertia['headers']['x-inertia'] ?? '') === 'true', '/: missing X-Inertia response header');
    requireCondition(str_contains($inertia['headers']['content-type'] ?? '', 'application/json'), '/: Inertia response is not JSON');
    $inertiaPage = json_decode($inertia['body'], true, flags: JSON_THROW_ON_ERROR);
    requireCondition(is_array($inertiaPage) && ($inertiaPage['component'] ?? null) === 'Home', '/: unexpected Inertia JSON component');
    echo "/: Inertia JSON OK\n";

    preg_match_all('~(?:src|href)="(/build/assets/[^"?]+\.(?:js|css))"~', $html['body'], $assetMatches);
    $hasScript = $hasStylesheet = false;
    foreach (array_unique($assetMatches[1]) as $asset) {
        $response = request($client, $baseUrl . $asset);
        requireCondition($response['status'] === 200, $asset . ': asset returned HTTP ' . $response['status']);
        $contentType = $response['headers']['content-type'] ?? '';
        if (str_ends_with($asset, '.js')) {
            $hasScript = true;
            requireCondition(str_contains($contentType, 'javascript'), $asset . ': asset is not JavaScript');
        } else {
            $hasStylesheet = true;
            requireCondition(str_contains($contentType, 'text/css'), $asset . ': asset is not CSS');
        }
    }
    requireCondition($hasScript && $hasStylesheet, 'HTML must load both production JavaScript and CSS');
    echo "Production browser assets OK\n";

    foreach (['/.env', '/composer.json', '/config/di/services.neon', '/build/.vite/manifest.json', '/assets/js/app.ts'] as $path) {
        $response = request($client, $baseUrl . $path);
        requireCondition($response['status'] === 404, $path . ': private/source path returned HTTP ' . $response['status']);
    }
    echo "Private files and frontend sources are not publicly served\n";

    // The production path: NGINX talks FastCGI to RoadRunner and passes the scheme and the host as FastCGI parameters.
    $fastcgiAddress = getenv('HTTP_SMOKE_FASTCGI_ADDRESS') ?: 'tcp://127.0.0.1:9000';
    $fastcgiHealth = fastcgiRequest($fastcgiAddress, '/up');
    requireCondition($fastcgiHealth['status'] === 200, 'FastCGI /up: health check returned HTTP ' . $fastcgiHealth['status']);
    $fastcgiHealthBody = json_decode($fastcgiHealth['body'], true, flags: JSON_THROW_ON_ERROR);
    requireCondition(is_array($fastcgiHealthBody) && ($fastcgiHealthBody['status'] ?? null) === 'ok', 'FastCGI /up: unexpected health payload');

    $fastcgiInertia = fastcgiRequest($fastcgiAddress, '/', [
        'HTTP_ACCEPT'           => 'text/html, application/xhtml+xml',
        'HTTP_X_INERTIA'        => 'true',
        'HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest',
        'HTTP_X_FORWARDED_PROTO' => 'https',
        'HTTP_X_FORWARDED_HOST' => 'app.test',
        'HTTP_HOST'             => 'app.test',
        'HTTPS'                 => 'on',
    ]);
    requireCondition($fastcgiInertia['status'] === 200, 'FastCGI /: Inertia returned HTTP ' . $fastcgiInertia['status']);
    requireCondition(($fastcgiInertia['headers']['x-inertia'] ?? '') === 'true', 'FastCGI /: missing X-Inertia response header');
    $fastcgiPage = json_decode($fastcgiInertia['body'], true, flags: JSON_THROW_ON_ERROR);
    requireCondition(is_array($fastcgiPage) && ($fastcgiPage['component'] ?? null) === 'Home', 'FastCGI /: unexpected Inertia component');
    echo "FastCGI requests OK\n";
} catch (Throwable $exception) {
    fwrite(STDERR, 'HTTP smoke failed: ' . $exception->getMessage() . "\n");
    exit(1);
}
