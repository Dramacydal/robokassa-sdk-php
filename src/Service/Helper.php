<?php

namespace Robokassa\Service;

trait Helper
{
    public function isCustomParameter(string $name): bool
    {
        return preg_match('/^shp_/ui', $name);
    }
}
