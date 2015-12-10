<?php

namespace Brouzie\Sphinxy\Query;

/**
 * @method SimpleResultSet[] getIterator()
 */
class MultiResultSet extends ResultSet
{
    public function __construct(array $result, array $rawMeta)
    {
        parent::__construct(array_map(function($result) {
            return new SimpleResultSet($result);
        }, $result), $rawMeta);
    }

    public function merge(self $multiResultSet)
    {
        foreach ($multiResultSet as $name => $resultSet) {
            if (is_string($name)) {
                $this->result[$name] = $resultSet;
                //TODO: save meta
            } else {
                $this->result[] = $resultSet;
            }
        }
    }
}
