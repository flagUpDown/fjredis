<?php
namespace FlagUpDown\Iterator;

use FlagUpDown\FjRedis;

class ScanRelatedFactory
{
    public static function getClass(FjRedis $client, string $commandName, ...$args)
    {
        $className = __NAMESPACE__ . '\\' . $commandName . 'Iterator';
        return new $className($client, ...$args);
    }
}
