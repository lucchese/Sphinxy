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

    /**
     * @param array $default Workaround for http://sphinxsearch.com/bugs/view.php?id=2410
     *
     * @return array
     */
    public function getSingleRow($default = array())
    {
        if (count($this->result) > 1) {
            throw new NonUniqueResultException();
        }

        return isset($this->result[0]) ? $this->result[0] : $default;
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
