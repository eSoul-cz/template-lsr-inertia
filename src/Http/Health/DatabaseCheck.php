<?php

declare(strict_types=1);

namespace App\Http\Health;

/**
 * Answers whether the application database can be used right now.
 */
interface DatabaseCheck
{
    public function isAvailable(): bool;
}
