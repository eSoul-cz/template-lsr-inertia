<?php

declare(strict_types=1);

namespace App\Http\Health;

use Lsr\Db\Connection;
use Throwable;

/**
 * Runs a trivial query on the application database connection (opens the lazy connection when needed).
 */
final readonly class ConnectionDatabaseCheck implements DatabaseCheck
{
    public function __construct(private Connection $connection) {
    }

    public function isAvailable(): bool {
        try {
            $this->connection->query('SELECT 1');
        } catch (Throwable) {
            return false;
        }

        return true;
    }
}
