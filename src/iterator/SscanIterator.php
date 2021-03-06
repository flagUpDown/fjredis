<?php
namespace FlagUpDown\Iterator;

use FlagUpDown\FjRedis;

class SscanIterator extends CursorBasedIterator
{
    protected $key;

    public function __construct(FjRedis $client, string $key, string $match = null, int $count = null)
    {
        parent::__construct($client, $match, $count);

        $this->key = $key;
    }

    protected function _fetch()
    {
        return $this->client->sscan($this->key, $this->_getScanOptions());
    }

    protected function _next()
    {
        $this->position++;
        $this->currValue = array_shift($this->elements);
    }
}
