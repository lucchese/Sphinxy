<?php

namespace Brouzie\Sphinxy\Query;

class ResultSet implements \IteratorAggregate
{
    protected $result;

    protected $meta;

    public function __construct(array $result, $meta)
    {
        $this->result = $result;
        $this->meta = $meta;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->result);
    }

    public function getTotalCount()
    {
        return $this->meta['total'];
    }
}
