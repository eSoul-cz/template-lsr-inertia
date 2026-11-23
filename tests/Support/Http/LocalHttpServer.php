<?php

declare(strict_types=1);

namespace Tests\Support\Http;

use RuntimeException;

/**
 * A PHP built-in web server on a free localhost port that echoes every request (tests/Fixtures/http/router.php).
 * Start it in setUp and stop it in tearDown.
 */
final class LocalHttpServer
{
    /** @param resource $process */
    private function __construct(private $process, public readonly string $baseUrl) {
    }

    public static function start(): self {
        $port = self::freePort();
        $process = proc_open(
            [PHP_BINARY, '-S', '127.0.0.1:' . $port, ROOT . 'tests/Fixtures/http/router.php'],
            [1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']],
            $pipes,
        );
        if ($process === false) {
            throw new RuntimeException('Cannot start the PHP built-in server.');
        }

        for ($attempt = 0; $attempt < 100; $attempt++) {
            $socket = @fsockopen('127.0.0.1', $port);
            if ($socket !== false) {
                fclose($socket);

                return new self($process, 'http://127.0.0.1:' . $port);
            }
            usleep(50_000);
        }

        proc_terminate($process);
        proc_close($process);
        throw new RuntimeException('The PHP built-in server did not start listening on port ' . $port . '.');
    }

    /** A localhost port nothing listens on (right after the call). */
    public static function freePort(): int {
        $probe = stream_socket_server('tcp://127.0.0.1:0', $errorCode, $errorMessage);
        if ($probe === false) {
            throw new RuntimeException('Cannot find a free port: ' . $errorMessage);
        }
        $name = (string) stream_socket_get_name($probe, false);
        fclose($probe);

        return (int) substr($name, (int) strrpos($name, ':') + 1);
    }

    public function stop(): void {
        proc_terminate($this->process);
        proc_close($this->process);
    }
}
