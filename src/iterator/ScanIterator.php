<?php
namespace FlagUpDown\Iterator;

use FlagUpDown\FjRedis;

class ScanIterator extends CursorBasedIterator
{
    public function __construct(FjRedis $client, string $match = null, int $count = null)
    {
        parent::__construct($client, $match, $count);
    }

    protected function _fetch()
    {
        return $this->client->scan($this->_getScanOptions());
    }

    protected function _next()
    {
        $this->position++;
        $this->currValue = array_shift($this->elements);
    }
}
