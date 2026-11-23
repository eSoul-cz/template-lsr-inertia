<?php

declare(strict_types=1);

namespace App\Http\Templates;

use Lsr\Core\Controllers\TemplateParameters;

/**
 * Props of the `Home` Inertia page. The inherited layout parameters (page, app, request, ...) stay
 * available to the Latte layout and are excluded from the Inertia props.
 */
final class HomeParameters extends TemplateParameters
{
    use AutoFillParameters;

    public string $appName = '';
}
