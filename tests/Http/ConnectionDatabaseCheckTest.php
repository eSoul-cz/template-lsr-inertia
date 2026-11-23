<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Health\ConnectionDatabaseCheck;
use Lsr\Caching\Cache;
use Lsr\Db\Connection;
use Lsr\Serializer\Mapper;
use Nette\Caching\Storages\MemoryStorage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Serializer;

#[CoversClass(ConnectionDatabaseCheck::class)]
final class ConnectionDatabaseCheckTest extends TestCase
{
    protected function setUp(): void {
        if ( ! extension_loaded('pdo_sqlite')) {
            self::markTestSkipped('pdo_sqlite is required.');
        }
    }

    #[Test]
    public function is_available_when_the_connection_answers(): void {
        $check = new ConnectionDatabaseCheck($this->connection('sqlite::memory:'));

        self::assertTrue($check->isAvailable());
    }

    #[Test]
    public function is_unavailable_when_the_connection_fails(): void {
        // SQLite cannot open a directory as a database file.
        $check = new ConnectionDatabaseCheck($this->connection('sqlite:' . sys_get_temp_dir()));

        self::assertFalse($check->isAvailable());
    }

    private function connection(string $dsn): Connection {
        return new Connection(
            new Cache(new MemoryStorage(), 'health-test', false),
            new Mapper(new Serializer()),
            ['driver' => 'pdo', 'dsn' => $dsn, 'lazy' => true],
        );
    }
}
