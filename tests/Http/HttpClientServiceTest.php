<?php

declare(strict_types=1);

namespace Tests\Http;

use App\Core\App;
use App\Http\Client\CurlHttpClient;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientInterface;
use Tests\Support\Http\FakeHttpClient;

final class HttpClientServiceTest extends TestCase
{
    protected function setUp(): void {
        // The container validates its temp directories at build time (include/config.php creates them at runtime).
        foreach ([TMP_DIR, TMP_DIR . 'cache/', TMP_DIR . 'latte/'] as $directory) {
            if ( ! is_dir($directory)) {
                mkdir($directory, 0777, true);
            }
        }

        App::setupDi();
    }

    protected function tearDown(): void {
        FakeHttpClient::release(App::getContainer());
    }

    #[Test]
    public function the_container_provides_the_curl_client_as_the_psr_18_client(): void {
        self::assertInstanceOf(CurlHttpClient::class, App::getContainer()->getByType(ClientInterface::class));
    }

    #[Test]
    public function a_test_binds_a_fake_instead_of_the_client(): void {
        $fake = FakeHttpClient::install(App::getContainer())->queue(new Response(202));

        $client = App::getContainer()->getByType(ClientInterface::class);
        $response = $client->sendRequest(new Request('POST', 'https://api.example.com/orders'));

        self::assertSame($fake, $client);
        self::assertSame(202, $response->getStatusCode());
        self::assertCount(1, $fake->requests);
        self::assertSame('https://api.example.com/orders', (string) $fake->requests[0]->getUri());
    }

    #[Test]
    public function releasing_the_fake_brings_the_real_client_back(): void {
        FakeHttpClient::install(App::getContainer());
        FakeHttpClient::release(App::getContainer());

        self::assertInstanceOf(CurlHttpClient::class, App::getContainer()->getByType(ClientInterface::class));
    }
}
