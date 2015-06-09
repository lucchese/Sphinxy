<?php

namespace Brouzie\Sphinxy\Pagerfanta\Adapter;

use Brouzie\Sphinxy\Query\ResultSet;
use Brouzie\Sphinxy\QueryBuilder;
use Pagerfanta\Adapter\AdapterInterface;

/**
 * Sphinxy Pagerfanta Adapter
 *
 * @author Konstantin.Myakshin <koc-dp@yandex.ru>
 */
class SphinxyQbAdapter implements AdapterInterface
{
    /**
     * @var QueryBuilder
     */
    protected $qb;

    /**
     * @var ResultSet|null
     */
    protected $previousResultSet;

    public function __construct(QueryBuilder $qb)
    {
        $this->qb = $qb;
    }

    /**
     * @inheritdoc
     */
    public function getNbResults()
    {
        if (null !== $this->previousResultSet) {
            return $this->previousResultSet->getAllowedCount();
        }

        $this->getSlice(0, 1);

        return $this->previousResultSet->getAllowedCount();
    }

    /**
     * @inheritdoc
     */
    public function getSlice($offset, $length)
    {
        $this->previousResultSet = $this
            ->qb
            ->setMaxResults($length)
            ->setFirstResult($offset)
            ->getResult();

        return $this->previousResultSet->getIterator();
    }

    /**
     * @return ResultSet|null
     */
    public function getPreviousResultSet()
    {
        return $this->previousResultSet;
    }
}
