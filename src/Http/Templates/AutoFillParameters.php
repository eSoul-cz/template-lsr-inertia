<?php

declare(strict_types=1);

namespace App\Http\Templates;

use Lsr\Core\Controllers\TemplateParameters;

trait AutoFillParameters
{
    /**
     * @param array<mixed>|TemplateParameters $params Controller parameters; only named entries matching a property are copied
     */
    public function __construct(array | TemplateParameters $params) {
        if ($params instanceof TemplateParameters) {
            $params = get_object_vars($params);
        }
        foreach ($params as $name => $value) {
            if (is_string($name) && property_exists($this, $name)) {
                $this->$name = $value;
            }
        }
    }
}
