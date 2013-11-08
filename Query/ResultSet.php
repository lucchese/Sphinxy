<?php

namespace Brouzie\Sphinxy\Query;

class ResultSet implements \IteratorAggregate
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

    public function getTotalCount()
    {
        return $this->meta['total'];
    }
}
