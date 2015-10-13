<?php

namespace Brouzie\Sphinxy\Query;

class SimpleResultSet implements \IteratorAggregate, \Countable
{
    protected $result;

    public function __construct(array $result)
    {
        $this->result = $result;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->result);
    }

    public function count()
    {
        return count($this->result);
    }
}
