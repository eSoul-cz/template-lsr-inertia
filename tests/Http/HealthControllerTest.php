<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Http\Controllers\Health;
use App\Http\Health\DatabaseCheck;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Health::class)]
final class HealthControllerTest extends TestCase
{
    #[Test]
    public function reports_ok_when_the_database_answers(): void {
        $response = new Health($this->database(true))->show();

        self::assertSame(200, $response->getStatusCode());
        self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame(['status' => 'ok'], $this->body($response->getBody()->__toString()));
    }

    #[Test]
    public function reports_unavailable_when_the_database_does_not_answer(): void {
        $response = new Health($this->database(false))->show();

        self::assertSame(503, $response->getStatusCode());
        self::assertSame('no-store', $response->getHeaderLine('Cache-Control'));
        self::assertSame(['status' => 'unavailable'], $this->body($response->getBody()->__toString()));
    }

    private function database(bool $available): DatabaseCheck {
        return new readonly class ($available) implements DatabaseCheck {
            public function __construct(private bool $available) {
            }

            public function isAvailable(): bool {
                return $this->available;
            }
        };
    }

    /** @return array<mixed> */
    private function body(string $json): array {
        $data = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        self::assertIsArray($data);

        return $data;
    }
}
