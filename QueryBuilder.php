<?php

namespace Brouzie\Sphinxy;

class QueryBuilder
{
    const TYPE_INSERT = 1;
    const TYPE_SELECT = 2;
    const TYPE_UPDATE = 3;
    const TYPE_DELETE = 4;
    const TYPE_REPLACE = 5;

    const STATE_DIRTY = 0;
    const STATE_CLEAN = 1;

    /**
     * @var Connection
     */
    private $conn;

    private $type;

    private $state;

    private $sql;

    private $sqlParts = array(
        'select' => array(),
        'from' => array(),
        'where' => array(),
        'groupBy' => array(),
        'orderBy' => array(),
        'values' => array(),
    );

    private $options = array();

    private $firstResult = null;

    private $maxResults = null;

    private $parametersCounter = 0;

    private $params = array();

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function select($select = null)
    {
        $this->type = self::TYPE_SELECT;
        if (empty($select)) {
            return $this;
        }

        return $this->add('select', (array)$select);
    }

    public function addSelect($select)
    {
        $this->type = self::TYPE_SELECT;

        return $this->add('select', (array)$select, true);
    }

    public function update($index)
    {
        $this->type = self::TYPE_UPDATE;

        return $this->add('from', array(
                'table' => $index,
            ));
    }

    public function insert($index)
    {
        $this->type = self::TYPE_INSERT;

        $this->resetQueryPart('from');

        return $this->add('from', array(
                'table' => $index,
            ), true);
    }

    public function replace($index)
    {
        $this->type = self::TYPE_REPLACE;

        $this->resetQueryPart('from');

        return $this->add('from', array(
                'table' => $index,
            ), true);
    }

    public function delete($index)
    {
        $this->type = self::TYPE_DELETE;

        return $this->add('from', array(
                'table' => $index,
            ));
    }

    public function set($key, $value)
    {
        return $this->add('set', $key .' = ' . $value, true);
    }

    public function values(array $values)
    {
        $this->resetQueryPart('values');

        return $this->add('values', $values, true);
    }

    public function addValues(array $values)
    {
        return $this->add('values', $values, true);
    }

    public function from($index)
    {
        $this->resetQueryPart('from');

        return $this->add('from', array(
                'table' => $index,
            ), true);
    }

    public function addFrom($inxex)
    {
        return $this->add('from', array(
                'table' => $inxex,
            ), true);
    }

    public function where($where)
    {
        $this->resetQueryPart('where');

        return $this->add('where', $where, true);
    }

    public function andWhere($where)
    {
        return $this->add('where', $where, true);
    }

    public function groupBy($groupBy, $limit = null)
    {
        //FIXME: add support of limit
        return $this->add('groupBy', $groupBy);
    }

    public function addGroupBy($groupBy, $limit = null)
    {
        //FIXME: add support of limit
        return $this->add('groupBy', $groupBy, true);
    }

    public function withinGroupOrderBy()
    {
    }

    public function orderBy($sort, $order = null)
    {
        return $this->add('orderBy', $sort.$this->getDirection($sort, $order));
    }

    public function addOrderBy($sort, $order = null)
    {
        return $this->add('orderBy', $sort.$this->getDirection($sort, $order), true);
    }

    public function setOption($name, $value)
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function setMaxResults($limit)
    {
        $this->state = self::STATE_DIRTY;
        $this->maxResults = $limit;

        return $this;
    }

    public function setFirstResult($offset)
    {
        $this->state = self::STATE_DIRTY;
        $this->firstResult = $offset;

        return $this;
    }

    public function setParameter($key, $value)
    {
        $this->params[$key] = $value;

        return $this;
    }

    /**
     * Creates a new named parameter and bind the value $value to it.
     *
     * @param mixed  $value
     * @param string $prefix The name to bind with.
     *
     * @return string the placeholder name used.
     */
    public function createParameter($value, $prefix = 'gen_')
    {
        $this->parametersCounter++;
        $prefix .= $this->parametersCounter;
        $this->setParameter($prefix, $value);

        return ':' . $prefix;
    }

    public function getParameters()
    {
        return $this->params;
    }

    public function execute()
    {
        return $this->conn->executeUpdate($this->getSql(), $this->params);
    }

    public function getResult()
    {
        return $this->conn->executeQuery($this->getSql(), $this->params);
    }

    public function getSql()
    {
        if (self::STATE_CLEAN === $this->state) {
            return $this->sql;
        }

        switch ($this->type) {
            case self::TYPE_SELECT:
                $this->sql = $this->buildSqlForSelect();
                break;

            case self::TYPE_INSERT:
            case self::TYPE_REPLACE:
                $this->sql = $this->buildSqlForInsert();
                break;

            case self::TYPE_UPDATE:
                $this->sql = $this->buildSqlForUpdate();
                break;

            case self::TYPE_DELETE:
                $this->sql = $this->buildSqlForDelete();
                break;
        }

        $this->state = self::STATE_CLEAN;

        return $this->sql;
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * The available parts are: 'select', 'from', 'set', 'where',
     * 'groupBy', 'having' and 'orderBy'.
     *
     * @param string $sqlPartName
     * @param string $sqlPart
     * @param boolean $append
     *
     * @return static This QueryBuilder instance.
     */
    protected function add($sqlPartName, $sqlPart, $append = false)
    {
        $isArray = is_array($sqlPart);
        $isMultiple = is_array($this->sqlParts[$sqlPartName]);

        if ($isMultiple && !$isArray) {
            $sqlPart = array($sqlPart);
        }

        $this->state = self::STATE_DIRTY;

        if (!$append) {
            $this->sqlParts[$sqlPartName] = $sqlPart;

            return $this;
        }

        if ($sqlPartName == "orderBy" || $sqlPartName == "groupBy" || $sqlPartName == "select" || $sqlPartName == "set") {
            foreach ($sqlPart as $part) {
                $this->sqlParts[$sqlPartName][] = $part;
            }
        } else {
            if ($isArray && is_array($sqlPart[key($sqlPart)])) {
                $key = key($sqlPart);
                $this->sqlParts[$sqlPartName][$key][] = $sqlPart[$key];
            } else {
                if ($isMultiple) {
                    $this->sqlParts[$sqlPartName][] = $sqlPart;
                } else {
                    $this->sqlParts[$sqlPartName] = $sqlPart;
                }
            }
        }

        return $this;
    }

    /**
     * Gets a query part by its name.
     *
     * @param string $queryPartName
     *
     * @return mixed
     */
    protected function getQueryPart($queryPartName)
    {
        return $this->sqlParts[$queryPartName];
    }

    protected function resetQueryPart($queryPartName)
    {
        $this->sqlParts[$queryPartName] = is_array($this->sqlParts[$queryPartName])
            ? array() : null;

        $this->state = self::STATE_DIRTY;

        return $this;
    }

    protected function buildSqlForSelect()
    {
        $query = 'SELECT ' . implode(', ', $this->sqlParts['select']) . ' FROM ';

        $fromClauses = array();
        foreach ($this->sqlParts['from'] as $from) {
            $fromClauses[] = $from['table'];
        }

        $query .= implode(', ', $fromClauses)
            . (count($this->sqlParts['where']) ? ' WHERE ' . $this->buildWherePart() : '')
            . ($this->sqlParts['groupBy'] ? ' GROUP BY ' . implode(', ', $this->sqlParts['groupBy']) : '')
            . ($this->sqlParts['orderBy'] ? ' ORDER BY ' . implode(', ', $this->sqlParts['orderBy']) : '');

        //TODO: inject limit, skip as parameters for better caching? Or just move caching to upper layer
        if ($this->maxResults) {
            $query .= ' LIMIT '.(int)$this->maxResults;
            if ($this->firstResult) {
                $query .= ', '.(int)$this->firstResult;
            }
        }

        if ($this->options) {
            $optionsClauses = array();
            foreach ($this->options as $optionName => $optionValue) {
                $optionsClauses[] = $optionName . ' = ' . $optionValue;
            }
            $query .= ' OPTION ' . implode(', ', $optionsClauses);
        }

        return $query;
    }

    protected function buildSqlForInsert()
    {
        $fromClauses = array();
        foreach ($this->sqlParts['from'] as $from) {
            $fromClauses[] = $from['table'];
        }

        $columns = array();
        $valuesSets = array();
        foreach ($this->sqlParts['values'] as $value) {
            //TODO: check columns
            $columns = array_keys($value);
            $valuesSets[] = '(' . implode(', ', $value) . ')';
        }

        //TODO: only one index allowed in insert?
        $query = ($this->type === self::TYPE_REPLACE ? 'REPLACE' : 'INSERT')
            . ' INTO ' . implode(', ', $fromClauses)
            . ' (' . implode(', ', $columns) . ') VALUES ' . implode(', ', $valuesSets);

        return $query;
    }

    protected function buildSqlForUpdate()
    {
        $table = $this->sqlParts['from']['table'];
        $query = 'UPDATE '.$table
            .' SET '.implode(", ", $this->sqlParts['set'])
            .(count($this->sqlParts['where']) ? ' WHERE '.$this->buildWherePart() : '');

        return $query;
    }

    protected function buildSqlForDelete()
    {
        $table = $this->sqlParts['from']['table'];
        $query = 'DELETE FROM '.$table.(count($this->sqlParts['where']) ? ' WHERE '.$this->buildWherePart() : '');

        return $query;
    }

    protected function buildWherePart()
    {
        $whereParts = array();
        foreach ($this->sqlParts['where'] as $where) {
            $whereParts[] = $where[0];
        }

        return implode(' AND ', $whereParts);
    }

    protected function getDirection($sort, $order)
    {
        if (strtoupper($order) === 'DESC') {
            return ' DESC';
        } elseif (null === $order && strtoupper($sort) === 'RAND()') {
            return '';
        }

        return ' ASC';
    }
}
