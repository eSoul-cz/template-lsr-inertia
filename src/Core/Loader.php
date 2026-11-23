<?php

declare(strict_types=1);

namespace App\Core;

use Dibi\DriverException;
use Dibi\Exception;
use Lsr\Db\Connection;
use Lsr\Db\DB;
use RuntimeException;

/**
 * Loader class to prevent any unnecessary global variable.
 */
class Loader
{
    /**
     * Initialize everything necessary.
     *
     * @post URL request is parsed
     * @post DI container is built and initialized
     * @post Database connection is registered (lazily unless DB_LAZY=false)
     */
    public static function init(): void {
        App::prettyUrl();
        App::setupDi();
        App::getContainer()->initialize();

        self::initDB();
    }

    /**
     * Initialize the database connection. Skipped when the `noDb` environment variable is set (tests, builds).
     *
     * @throws RuntimeException
     */
    public static function initDB(): void {
        if (isset($_ENV['noDb'])) {
            return;
        }
        try {
            $db = App::getService('db.connection');
            assert($db instanceof Connection);
            DB::init($db);
        } catch (Exception | DriverException $e) {
            App::getInstance()->getLogger()->error(
                'Database initialization failed.',
                [
                    'exceptionClass' => $e::class,
                    'errorCode'      => $e->getCode(),
                ],
            );
            throw new RuntimeException('Cannot connect to the database.', (int) $e->getCode(), $e);
        }
    }
}
