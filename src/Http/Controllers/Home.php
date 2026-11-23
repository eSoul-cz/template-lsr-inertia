<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Core\Controller;
use App\Http\Templates\HomeParameters;
use Lsr\Inertia\Http\WithInertia;
use Psr\Http\Message\ResponseInterface;

class Home extends Controller
{
    use WithInertia;

    public function show(): ResponseInterface {
        $this->title = 'Home';

        $parameters = new HomeParameters($this->params);
        $parameters->appName = $this->getApp()->getAppName();

        return $this->inertia('Home', $parameters);
    }
}
