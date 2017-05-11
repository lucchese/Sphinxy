<?php

namespace Brouzie\Sphinxy\Indexer;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;

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
        $this->checkConnection();

        $rootId = $this->getRootIdentifier();
        $qb = $this->getQueryBuilder();

        $range = $qb
            ->select($qb->expr()->min($rootId), $qb->expr()->max($rootId))
            ->getQuery()
            ->getSingleResult();

        return array('min' => array_shift($range), 'max' => array_shift($range));
    }

    public function getItemsByIds(array $ids)
    {
        $this->checkConnection();
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
        $this->checkConnection();
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

    private function checkConnection()
    {
        $connection = $this->em->getConnection();
        if ($connection->ping() === false) {
            $connection->close();
            $connection->connect();
        }
    }
}
