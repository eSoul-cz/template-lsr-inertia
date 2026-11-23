<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base controller: page titles are plain strings, not gettext keys.
 */
class Controller extends \Lsr\Core\Controllers\Controller
{
    public function getTitle(): string {
        $appName = $this->getApp()->getAppName();
        if ($this->title === '') {
            return $appName;
        }

        return $appName === '' ? $this->title : $this->title . ' | ' . $appName;
    }
}
