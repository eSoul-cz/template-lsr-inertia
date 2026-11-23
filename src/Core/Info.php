<?php

declare(strict_types=1);

namespace App\Core;

use Dibi\Exception;
use Lsr\Caching\Cache;
use Lsr\Db\DB;
use Throwable;

/**
 * Key-value read-write storage (in the `page_info` database table).
 *
 * Allows storing any serializable values. Used by the installer to remember table versions.
 */
class Info
{
    public const string TABLE = 'page_info';
    /** @var array<string, mixed> */
    private static array $info = [];

    public static function get(string $key, mixed $default = null, bool $useCache = true): mixed {
        if ( ! $useCache) {
            $value = self::getValue($key);
            if ($value === null) {
                return $default;
            }
            $value = self::getUnserialized($value, $key);
            self::set($key, $value);
            return $value;
        }

        if (isset(self::$info[$key])) {
            return self::$info[$key];
        }

        $cache = App::getService('cache');
        assert($cache instanceof Cache);
        try {
            /** @var string|null $value */
            $value = $cache->load(
                'info.' . $key,
                static fn () => self::getValue($key),
                [
                    'tags' => ['info', 'info/' . $key],
                ],
            );
        } catch (Throwable) {
            $value = null;
        }
        if ($value === null) {
            return $default;
        }
        return self::getUnserialized($value, $key);
    }

    private static function getValue(string $key): ?string {
        try {
            $value = DB::select(self::TABLE, '[value]')
                ->where('[key] = %s', $key)
                ->cacheTags('info', 'info/' . $key)
                ->fetchSingle(false);

            return is_string($value) ? $value : null;
        } catch (Exception) {
            return null;
        }
    }

    private static function getUnserialized(string $value, string $key): mixed {
        $unserialized = igbinary_unserialize($value);
        if (
            ($unserialized === false && $value !== igbinary_serialize(false)) ||
            ($unserialized === null && $value !== igbinary_serialize(null))
        ) {
            // Fallback to normal PHP serialization, then re-serialize the value
            $unserialized = unserialize($value);
            self::set($key, $unserialized);
        }
        self::$info[$key] = $unserialized;
        return $unserialized;
    }

    /**
     * @throws Exception
     */
    public static function set(string $key, mixed $value): void {
        self::$info[$key] = $value;
        $serialized = igbinary_serialize($value);
        DB::replace(
            self::TABLE,
            [
                [
                    'key'   => $key,
                    'value' => $serialized,
                ],
            ],
        );
        $cache = App::getService('cache');
        assert($cache instanceof Cache);
        $cache->save('info.' . $key, $serialized, [Cache::Tags => ['info', 'info/' . $key]]);
    }

    public static function clearStaticCache(): void {
        self::$info = [];
    }
}
