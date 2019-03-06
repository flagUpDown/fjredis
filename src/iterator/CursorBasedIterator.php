<?php
namespace FlagUpDown\Iterator;

use FlagUpDown\FjRedis;

abstract class CursorBasedIterator implements \Iterator
{
    protected $client;
    protected $match;
    protected $count;

    protected $cursor;
    protected $position;
    protected $elements;
    protected $currValue;
    protected $valid;
    protected $lastCursor;

    public function __construct(FjRedis $client, string $match = null, int $count = null)
    {
        $this->client = $client;
        $this->match  = $match;
        $this->count  = $count;

        $this->_reset();
    }

    public function current()
    {
        return $this->currValue;
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        while (empty($this->elements) && !$this->lastCursor) {
            list($this->cursor, $this->elements) = $this->_fetch();
            if (!$this->cursor) {
                $this->lastCursor = true;
            }
        }
        if (empty($this->elements)) {
            $this->valid = false;
        } else {
            $this->_next();
        }
    }

    public function rewind()
    {
        $this->_reset();
        $this->next();
    }

    public function valid()
    {
        return $this->valid;
    }

    protected function _reset()
    {
        $this->elements   = [];
        $this->cursor     = 0;
        $this->position   = -1;
        $this->currValue  = null;
        $this->valid      = true;
        $this->lastCursor = false;
    }

    protected function _getScanOptions()
    {
        $result   = [];
        $result[] = $this->cursor;
        if ($this->match) {
            $result[] = 'match';
            $result[] = $this->match;
        }
        if ($this->count) {
            $result[] = 'count';
            $result[] = $this->count;
        }
        return $result;
    }

    abstract protected function _fetch();
    abstract protected function _next();
}
