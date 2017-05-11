<?php

namespace Brouzie\Sphinxy;

class QueryBuilder
{
    const TYPE_INSERT = 1;
    const TYPE_SELECT = 2;
    const TYPE_UPDATE = 3;
    const TYPE_DELETE = 4;
    const TYPE_REPLACE = 5;

    /**
     * @var Connection
     */
    private $conn;

    private $type;

    private $sqlParts = array(
        'select' => array(),
        'from' => array(),
        'where' => array(),
        'groupBy' => array(),
        'groupByLimit' => null,
        'withinGroupOrderBy' => array(),
        'orderBy' => array(),
        'facet' => array(),
        'resultSetNames' => array(0),
        'set' => array(),
        'values' => array(),
        'options' => array(),
        'firstResult' => 0,
        'maxResults' => null,
    );

    private static $multipleParts = array(
        'select' => true,
        'from' => true,
        'where' => true,
        'groupBy' => true,
        'groupByLimit' => false,
        'withinGroupOrderBy' => true,
        'orderBy' => true,
        'facet' => true,
        'resultSetNames' => true,
        'set' => true,
        'values' => true,
        'options' => true,
        'firstResult' => false,
        'maxResults' => false,
    );

    private $isDirty = true;

    private $sql;

    private $parameters = array();

    private $parametersCounter = 0;

    public function __construct(Connection $conn)
    {
        $this->conn = $conn;
    }

    public function getEscaper()
    {
        return $this->conn->getEscaper();
    }

    public function select($select = null)
    {
        $this->type = self::TYPE_SELECT;
        if (null === $select) {
            return $this;
        }

        return $this->add('select', (array) $select);
    }

    public function addSelect($select)
    {
        $this->type = self::TYPE_SELECT;

        return $this->add('select', (array) $select, true);
    }

    public function update($index)
    {
        $this->type = self::TYPE_UPDATE;

        return $this->add('from', array('table' => $index));
    }

    public function insert($index)
    {
        $this->type = self::TYPE_INSERT;

        return $this->add('from', array('table' => $index));
    }

    public function replace($index)
    {
        $this->type = self::TYPE_REPLACE;

        return $this->add('from', array('table' => $index));
    }

    public function delete($index)
    {
        $this->type = self::TYPE_DELETE;

        return $this->add('from', array('table' => $index));
    }

    public function set($key, $value)
    {
        return $this->add('set', compact('key', 'value'), true);
    }

    public function values(array $values)
    {
        return $this->add('values', $values);
    }

    public function addValues(array $values)
    {
        return $this->add('values', $values, true);
    }

    public function from($index)
    {
        return $this->add('from', array('table' => $index));
    }

    public function addFrom($index)
    {
        return $this->add('from', array('table' => $index), true);
    }

    public function where($where)
    {
        return $this->add('where', $where);
    }

    public function andWhere($where)
    {
        return $this->add('where', $where, true);
    }

    public function groupBy($groupBy, $limit = null)
    {
        return $this
            ->add('groupBy', $groupBy)
            ->add('groupByLimit', $limit);
    }

    public function addGroupBy($groupBy)
    {
        return $this->add('groupBy', $groupBy, true);
    }

    public function withinGroupOrderBy($order, $direction = null)
    {
        return $this->add('withinGroupOrderBy', compact('order', 'direction'));
    }

    public function addWithinGroupOrderBy($order, $direction = null)
    {
        return $this->add('withinGroupOrderBy', compact('order', 'direction'), true);
    }

    /**
     * @param string|array $facet 'column1', or array('column1', 'column1') or array('column1' => 'column_alias', 'column2')
     * @param string $by
     * @param string $order
     * @param string $direction
     * @param int $limit
     * @param int $skip
     *
     * @return $this
     */
    public function facet($facet, $by = null, $order = null, $direction = null, $limit = null, $skip = 0)
    {
        $facet = (array) $facet;

        return $this->add('facet', compact('facet', 'by', 'order', 'direction', 'limit', 'skip'), true);
    }

    public function nameResultSet($name)
    {
        return $this->add('resultSetNames', $name, true);
    }

    public function orderBy($order, $direction = null)
    {
        return $this->add('orderBy', compact('order', 'direction'));
    }

    public function addOrderBy($order, $direction = null)
    {
        return $this->add('orderBy', compact('order', 'direction'), true);
    }

    public function setOption($name, $value)
    {
        return $this->add('options', compact('name', 'value'), true);
    }

    public function setMaxResults($limit)
    {
        return $this->add('maxResults', $limit);
    }

    public function setFirstResult($skip)
    {
        return $this->add('firstResult', $skip);
    }

    public function merge(self $qb)
    {
        //TODO: делать или не делать?
        // ...
    }

    public function setParameter($parameter, $value)
    {
        $this->parameters[$parameter] = $value;

        return $this;
    }

    /**
     * Creates a new named parameter and bind the value $value to it.
     *
     * @param string $value
     * @param string $prefix the name to bind with
     *
     * @return string the placeholder name used
     */
    public function createParameter($value, $prefix = 'gen_')
    {
        $prefix = preg_replace('/[^a-z0-9_]/ui', '_', $prefix);
        $prefix .= ++$this->parametersCounter;
        $this->setParameter($prefix, $value);

        return ':'.$prefix;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function execute()
    {
        return $this->conn->executeUpdate($this->getSql(), $this->parameters);
    }

    public function getResult()
    {
        return $this->conn->executeQuery($this->getSql(), $this->parameters);
    }

    public function getMultiResult()
    {
        return $this->conn->executeMultiQuery($this->getSql(), $this->parameters, array(), $this->sqlParts['resultSetNames']);
    }

    public function getSql()
    {
        if (!$this->isDirty) {
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

        $this->isDirty = false;

        return $this->sql;
    }

    /**
     * Either appends to or replaces a single, generic query part.
     *
     * @param string $sqlPartName
     * @param string|array $sqlPart
     * @param bool $append
     *
     * @return $this this QueryBuilder instance
     */
    protected function add($sqlPartName, $sqlPart, $append = false)
    {
        $this->isDirty = true;

        if (self::$multipleParts[$sqlPartName]) {
            if ($append) {
                $this->sqlParts[$sqlPartName][] = $sqlPart;
            } else {
                $this->sqlParts[$sqlPartName] = array($sqlPart);
            }
        } else {
            $this->sqlParts[$sqlPartName] = $sqlPart;
        }

        return $this;
    }

    protected function buildSqlForSelect()
    {
        $select = call_user_func_array('array_merge', $this->sqlParts['select']);
        $query = 'SELECT '.implode(', ', $select).' FROM ';

        $fromParts = array();
        foreach ($this->sqlParts['from'] as $from) {
            $table = $from['table'];
            if ($table instanceof static) {
                $fromParts[] = '('.$table->getSql().')';
                foreach ($table->getParameters() as $parameter => $value) {
                    $this->setParameter($parameter, $value);
                }
            } else {
                $fromParts[] = $table;
            }
        }

        $query .= implode(', ', $fromParts)
            .$this->buildWherePart()
            .$this->buildGroupByPart()
            .$this->buildOrderByPart();

        //TODO: inject limit, skip as parameters for better caching? Or just move caching to upper layer
        if ($this->sqlParts['maxResults']) {
            $query .= ' LIMIT '.(int) $this->sqlParts['firstResult'].', '.(int) $this->sqlParts['maxResults'];
        }

        $query .= $this->buildOptionsPart()
            .$this->buildFacetPart();

        return $query;
    }

    protected function buildSqlForInsert()
    {
        $columns = array();
        $valuesParts = array();
        foreach ($this->sqlParts['values'] as $value) {
            //TODO: check columns
            $columns = array_keys($value);
            $valuesParts[] = '('.implode(', ', $value).')';
        }

        $index = current($this->sqlParts['from'])['table'];
        $query = ($this->type === self::TYPE_REPLACE ? 'REPLACE' : 'INSERT')
            .' INTO '.$index
            .' ('.implode(', ', $columns).') VALUES '.implode(', ', $valuesParts);

        return $query;
    }

    protected function buildSqlForUpdate()
    {
        $index = current($this->sqlParts['from'])['table'];
        $setParts = array();
        foreach ($this->sqlParts['set'] as $setPart) {
            $setParts[] = $setPart['key'].' = '.$setPart['value'];
        }

        $query = 'UPDATE '.$index.' SET '.implode(', ', $setParts).$this->buildWherePart();

        return $query;
    }

    protected function buildSqlForDelete()
    {
        $index = current($this->sqlParts['from'])['table'];
        $query = 'DELETE FROM '.$index.$this->buildWherePart();

        return $query;
    }

    protected function buildWherePart()
    {
        if (!$this->sqlParts['where']) {
            return '';
        }

        return ' WHERE '.implode(' AND ', $this->sqlParts['where']);
    }

    protected function buildGroupByPart()
    {
        if (!$this->sqlParts['groupBy']) {
            return '';
        }

        $sql = ' GROUP'.($this->sqlParts['groupByLimit'] ? ' '.$this->sqlParts['groupByLimit'] : '')
            .' BY '.implode(', ', $this->sqlParts['groupBy']);

        if (!$this->sqlParts['withinGroupOrderBy']) {
            return $sql;
        }

        $orderByParts = array();
        foreach ($this->sqlParts['withinGroupOrderBy'] as $orderBy) {
            $orderByParts[] = $orderBy['order'].$this->getDirection($orderBy['order'], $orderBy['direction']);
        }

        return $sql.' WITHIN GROUP ORDER BY '.implode(', ', $orderByParts);
    }

    protected function buildOrderByPart()
    {
        if (!$this->sqlParts['orderBy']) {
            return '';
        }

        $orderByParts = array();
        foreach ($this->sqlParts['orderBy'] as $orderBy) {
            $orderByParts[] = $orderBy['order'].$this->getDirection($orderBy['order'], $orderBy['direction']);
        }

        return ' ORDER BY '.implode(', ', $orderByParts);
    }

    protected function buildOptionsPart()
    {
        if (!$this->sqlParts['options']) {
            return '';
        }

        $optionsParts = array();
        foreach ($this->sqlParts['options'] as $option) {
            $optionsParts[] = $option['name'].' = '.$option['value'];
        }

        return ' OPTION '.implode(', ', $optionsParts);
    }

    /**
     * Build FACET {expr_list} [BY {expr_list}] [ORDER BY {expr | FACET()} {ASC | DESC}] [LIMIT [offset,] count].
     *
     * @return string
     */
    protected function buildFacetPart()
    {
        if (!$this->sqlParts['facet']) {
            return '';
        }

        $facetParts = array();
        foreach ($this->sqlParts['facet'] as $facet) {
            $facetExpressions = array();
            foreach ($facet['facet'] as $key => $facetExpr) {
                if (is_int($key)) {
                    $facetExpressions[] = $facetExpr;
                } else {
                    $facetExpressions[] = $key.' AS '.$facetExpr;
                }
            }
            $facetPart = 'FACET '.implode(', ', $facetExpressions);
            if ($facet['by']) {
                $facetPart .= ' BY '.$facet['by'];
            }
            if ($facet['order']) {
                $facetPart .= ' ORDER BY '.$facet['order'].$this->getDirection($facet['order'], $facet['direction']);
            }
            if ($facet['limit']) {
                $facetPart .= ' LIMIT '.(int) $facet['skip'].', '.(int) $facet['limit'];
            }

            $facetParts[] = $facetPart;
        }

        return ' '.implode(' ', $facetParts);
    }

    protected function getDirection($order, $direction)
    {
        if (strtoupper($direction) === 'DESC') {
            return ' DESC';
        }

        if (null === $direction && strtoupper($order) === 'RAND()') {
            return '';
        }

        return ' ASC';
    }
}
