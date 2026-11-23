<?php

declare(strict_types=1);

namespace Tests\Support\Network;

/**
 * Replaces PHP's `http` and `https` stream wrappers for the test run: a stream to localhost (`localhost`, `127.0.0.0/8`,
 * `::1`) is opened by the original wrapper, a stream to any other host throws `NetworkAccessBlocked` before a connection
 * is attempted. It covers `file_get_contents()`, `fopen()` and the other functions that go through PHP stream wrappers.
 *
 * Not covered: raw sockets (`fsockopen()`, `stream_socket_client()`) and extensions with their own network stack (cURL);
 * review covers those. A pass-through stream does not populate `$http_response_header`.
 */
final class LocalOnlyStreamWrapper
{
    private const array SCHEMES = ['http', 'https'];

    private static bool $registered = false;

    /** @var resource|null Set by PHP to the stream context of the call. */
    public $context;

    /** @var resource|null */
    private $handle;

    public static function register(): void {
        if (self::$registered) {
            return;
        }

        foreach (self::SCHEMES as $scheme) {
            stream_wrapper_unregister($scheme);
            stream_wrapper_register($scheme, self::class);
        }
        self::$registered = true;
    }

    public static function isLocalHost(string $host): bool {
        $host = strtolower(trim($host, '[]'));

        return $host === 'localhost'
            || str_ends_with($host, '.localhost')
            || $host === '::1'
            || preg_match('~^127(?:\.\d{1,3}){3}$~', $host) === 1;
    }

    public function stream_open(string $path, string $mode, int $options, ?string &$opened_path): bool {
        $parts = parse_url($path);
        $scheme = strtolower($parts['scheme'] ?? '');
        if ( ! in_array($scheme, self::SCHEMES, true) || ! self::isLocalHost($parts['host'] ?? '')) {
            throw NetworkAccessBlocked::forUrl($path);
        }

        // The original wrapper is put back only for the duration of this call.
        stream_wrapper_restore($scheme);
        try {
            $handle = $this->context === null ? fopen($path, $mode) : fopen($path, $mode, false, $this->context);
        } finally {
            stream_wrapper_unregister($scheme);
            stream_wrapper_register($scheme, self::class);
        }
        if ($handle === false) {
            return false;
        }

        $this->handle = $handle;

        return true;
    }

    public function stream_read(int $count): string | false {
        return $this->handle === null || $count < 1 ? false : fread($this->handle, $count);
    }

    public function stream_write(string $data): int | false {
        return $this->handle === null ? false : fwrite($this->handle, $data);
    }

    public function stream_eof(): bool {
        return $this->handle === null || feof($this->handle);
    }

    /** @return array<int|string, int>|false */
    public function stream_stat(): array | false {
        return $this->handle === null ? false : fstat($this->handle);
    }

    public function stream_set_option(int $option, int $arg1, ?int $arg2): bool {
        return false;
    }

    public function stream_close(): void {
        if ($this->handle !== null) {
            fclose($this->handle);
            $this->handle = null;
        }
    }
}
