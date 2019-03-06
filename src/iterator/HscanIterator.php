<?php
namespace FlagUpDown\Iterator;

use FlagUpDown\FjRedis;

class HscanIterator extends CursorBasedIterator
{
    protected $key;

    public function __construct(FjRedis $client, string $key, string $match = null, int $count = null)
    {
        parent::__construct($client, $match, $count);

        $this->key = $key;
    }

    protected function _fetch()
    {
        return $this->client->hscan($this->key, $this->_getScanOptions());
    }

    protected function _next()
    {
        $this->position  = array_shift($this->elements);
        $this->currValue = array_shift($this->elements);
    }
}
