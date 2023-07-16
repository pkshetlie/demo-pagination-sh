<?php

namespace App\Routing;


use InvalidArgumentException;

class UrlGenerator
{

    public function __construct()
    {
    }

    public function generateByKey(string $key, array $parameters = [], ?int $siteId = null): string
    {
        return '';
    }

    public function generate(string $path, array $parameters = [], int $referenceType = null): string
    {
        return 'index.php?'.http_build_query($parameters);
    }
}
