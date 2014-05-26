<?php

namespace Brouzie\Sphinxy\Indexer;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\AbstractQuery;

abstract class DoctrineQbIndexer implements IndexerInterface
{
    /**
     * @var EntityManager
     */
    protected $em;

    public function __construct(EntityManager $em)
    {
        $this->em = $em;

        $this->em->getConfiguration()->setSQLLogger(null);
        $this->em->getConnection()->getConfiguration()->setSQLLogger(null);
    }

    public function getRangeCriterias()
    {
        //TODO: не работает select('MIN(p.id) AS min, MAX(p.id) AS max')
        $rootId = $this->getRootIdentifier();

        $min = $this->getQueryBuilder()
            ->select($rootId)
            ->orderBy($rootId, 'ASC')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult()
        ;

        $max = $this->getQueryBuilder()
            ->select($rootId)
            ->orderBy($rootId, 'DESC')
            ->getQuery()
            ->setMaxResults(1)
            ->getSingleScalarResult()
        ;

        return array('min' => $min, 'max' => $max);
    }

    public function getItemsByIds(array $ids)
    {
        $rootId = $this->getRootIdentifier();

        $items = $this->getQueryBuilder()
            ->andWhere(sprintf('%s IN (:ids)', $rootId))
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getResult($this->getHydrationMode())
        ;

        return $items;
    }

    public function getItemsByInterval($idFrom, $idTo)
    {
        $rootId = $this->getRootIdentifier();

        $items = $this->getQueryBuilder()
            ->andWhere(sprintf('%s >= :min AND %s < :max', $rootId, $rootId))
            ->setParameter('min', $idFrom)
            ->setParameter('max', $idTo)
            ->getQuery()
            ->getResult($this->getHydrationMode())
        ;

        return $items;
    }

    public function processItems(array $items)
    {
        return $items;
    }

    public function serializeItem($item)
    {
        return $item;
    }

    /**
     * @return \Doctrine\ORM\QueryBuilder
     */
    protected function getQueryBuilder()
    {
        return $this->em->getRepository($this->getEntityName())->createQueryBuilder('e');
    }

    protected function getEntityName()
    {
        throw new \BadMethodCallException('You should override this method.');
    }

    protected function getRootIdentifier()
    {
        return 'e.id';
    }

    protected function getHydrationMode()
    {
        return AbstractQuery::HYDRATE_ARRAY;
    }
}
