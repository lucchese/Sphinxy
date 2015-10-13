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
}
