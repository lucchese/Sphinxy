<?php

namespace Brouzie\Sphinxy\Pagerfanta\Adapter;

use Brouzie\Sphinxy\Query\ResultSet;
use Brouzie\Sphinxy\QueryBuilder;
use Pagerfanta\Adapter\LazyAdapterInterface;

// forward compatibility layer
if (!interface_exists('Pagerfanta\Adapter\LazyAdapterInterface')) {
    class_alias('Pagerfanta\Adapter\AdapterInterface', 'Pagerfanta\Adapter\LazyAdapterInterface');
}

/**
 * Sphinxy Pagerfanta Adapter
 *
 * @author Konstantin Myakshin <koc-dp@yandex.ru>
 */
class SphinxyQbAdapter implements LazyAdapterInterface
{
    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * @var ResultSet|null
     */
    protected $previousResultSet;

    protected $resultsFilterCallback;

    public function __construct(QueryBuilder $qb, callable $resultsFilterCallback = null)
    {
        $this->qb = $qb;
        $this->resultsFilterCallback = $resultsFilterCallback;
    }

    /**
     * {@inheritdoc}
     */
    public function getNbResults()
    {
        if (null !== $this->previousResultSet) {
            return $this->previousResultSet->getAllowedCount();
        }

        return $this->qb
            ->setMaxResults(1)
            ->setFirstResult(0)
            ->getResult()
            ->getAllowedCount();
    }

    /**
     * {@inheritdoc}
     */
    public function getSlice($offset, $length)
    {
        $this->previousResultSet = $this->qb
            ->setMaxResults($length)
            ->setFirstResult($offset)
            ->getResult();

        $result = $this->previousResultSet->getIterator();

        if (is_callable($this->resultsFilterCallback)) {
            $result = call_user_func($this->resultsFilterCallback, $result);
        }

        return $result;
    }

    /**
     * @return ResultSet|null
     */
    public function getPreviousResultSet()
    {
        return $this->previousResultSet;
    }
}
