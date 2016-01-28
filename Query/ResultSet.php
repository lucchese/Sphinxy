<?php

namespace Brouzie\Sphinxy\Query;

use Brouzie\Sphinxy\Exception\NonUniqueResultException;

class ResultSet extends SimpleResultSet
{
    protected $meta;

    public function __construct(array $result, array $rawMeta)
    {
        parent::__construct($result);

        $meta = array();
        foreach ($rawMeta as $row) {
            $meta[$row['Variable_name']] = $row['Value'];
        }

        $this->meta = $meta;
    }

    public function getSingleRow()
    {
        if (count($this->result) > 1) {
            throw new NonUniqueResultException();
        }

        return $this->result[0];
    }

    public function getMeta()
    {
        return $this->meta;
    }

    public function getAllowedCount()
    {
        return (int) $this->meta['total'];
    }

    public function getTotalCount()
    {
        return (int) $this->meta['total_found'];
    }
}
