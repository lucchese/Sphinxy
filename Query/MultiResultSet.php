<?php

namespace Brouzie\Sphinxy\Query;

/**
 * @method SimpleResultSet[] getIterator()
 */
class MultiResultSet extends ResultSet
{
    public function __construct(array $result, array $rawMeta)
    {
        parent::__construct(array_map(function ($result) {
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

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasResultSet($name)
    {
        return isset($this->result[$name]);
    }

    /**
     * @param string $name
     *
     * @return SimpleResultSet
     */
    public function getResultSet($name)
    {
        if (!$this->hasResultSet($name)) {
            throw new \InvalidArgumentException(sprintf('Result set with name "%s" not exists.', $name));
        }

        return $this->result[$name];
    }
}
