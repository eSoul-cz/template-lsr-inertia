<?php

declare(strict_types=1);

namespace Tests\Config;

use Pdo\Mysql;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class EnvironmentTest extends TestCase
{
    #[Test]
    public function the_database_session_runs_on_the_application_clock(): void {
        $parameters = $this->parameters();

        self::assertIsArray($parameters['db']);
        self::assertIsArray($parameters['db']['options']);
        self::assertSame("SET time_zone = '+00:00'", $parameters['db']['options'][Mysql::ATTR_INIT_COMMAND]);
    }

    #[Test]
    public function the_application_clock_is_utc(): void {
        self::assertSame('UTC', APP_TIMEZONE);
    }

    /** @return array<mixed> */
    private function parameters(): array {
        $config = require ROOT . 'config/di/environment.php';
        self::assertIsArray($config);
        self::assertIsArray($config['parameters']);

        return $config['parameters'];
    }
}
