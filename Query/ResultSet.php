<?php

namespace Brouzie\Sphinxy\Query;

class ResultSet implements \IteratorAggregate, \Countable
{
    protected $result;

    protected $meta;

    public function __construct(array $result, $rawMeta)
    {
        $this->result = $result;

        $meta = array();
        foreach ($rawMeta as $row) {
            $meta[$row['Variable_name']] = $row['Value'];
        }

        $this->meta = $meta;
    }

    public function getIterator()
    {
        return new \ArrayIterator($this->result);
    }

    public function count()
    {
        return count($this->result);
    }

    public function getAllowedCount()
    {
        return $this->meta['total'];
    }

    public function getTotalCount()
    {
        return $this->meta['total_found'];
    }
}
