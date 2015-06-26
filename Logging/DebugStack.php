<?php

namespace Brouzie\Sphinxy\Logging;

/**
 * Includes executed SQLs in a Debug Stack.
 *
 * @author Konstantin Myakshin <koc-dp@yandex.ru>
 */
class DebugStack implements LoggerInterface
{
    /**
     * READONLY
     * Executed SQL queries.
     *
     * @var array
     */
    public $queries = array();

    /**
     * @var float|null
     */
    protected $start;

    /**
     * @var integer
     */
    protected $currentQuery = 0;

    /**
     * {@inheritdoc}
     */
    public function startQuery($sql, array $params = null)
    {
        $this->start = microtime(true);
        $this->queries[++$this->currentQuery] = array('sql' => $sql, 'params' => $params, 'executionMS' => 0);
    }

    /**
     * {@inheritdoc}
     */
    public function stopQuery()
    {
        $this->queries[$this->currentQuery]['executionMS'] = microtime(true) - $this->start;
    }
}
