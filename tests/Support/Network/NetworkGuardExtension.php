<?php

declare(strict_types=1);

namespace Tests\Support\Network;

use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

/**
 * PHPUnit extension (registered in phpunit.xml) that blocks http(s) stream requests to anything but localhost.
 *
 * @see LocalOnlyStreamWrapper for what it covers and what it does not
 */
final class NetworkGuardExtension implements Extension
{
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void {
        LocalOnlyStreamWrapper::register();
    }
}
