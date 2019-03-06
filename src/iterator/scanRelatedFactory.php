<?php
namespace FlagUpDown\Iterator;

use FlagUpDown\FjRedis;

class ScanRelatedFactory
{
    protected $client;

    public function __construct(FjRedis $client)
    {
        $this->client = $client;
    }

    public function __call($name, $args)
    {
        $className = __NAMESPACE__ . '\\' . $name . 'Iterator';
        return new $className($this->client, ...$args);
    }
}
