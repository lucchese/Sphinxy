<?php

namespace Brouzie\Sphinxy;

use Brouzie\Sphinxy\Indexer\IndexerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IndexManager
{
    protected $conn;

    protected $container;

    protected $indexers = array();

    /**
     * @param Registry $registry
     * @param ContainerInterface $container
     * @param array $indexers
     */
    public function __construct(Registry $registry, ContainerInterface $container, array $indexers)
    {
        $this->conn = $registry->getConnection();
        $this->container = $container;
        $this->indexers = $indexers;
    }

    public function reindex($index, $batchSize = 1000, $batchCallback = null, array $rangeCriterias = array())
    {
        $logger = $this->conn->getLogger();
        $this->conn->setLogger(null);

        $indexer = $this->getIndexer($index);
        $range = array_replace($indexer->getRangeCriterias(), $rangeCriterias);

        $idFrom = $range['min'];
        do {
            $idTo = $idFrom + $batchSize;
            if (is_callable($batchCallback)) {
                call_user_func($batchCallback, array('id_from' => $idFrom, 'id_to' => $idTo, 'min_id' => $range['min'], 'max_id' => $range['max']));
            }

            $items = $indexer->getItemsByInterval($idFrom, $idTo);
            $this->processItems($index, $indexer, $items);
            $idFrom = $idTo;
        } while ($idFrom <= $range['max']);
        $this->conn->setLogger($logger);
    }

    public function reindexItems($index, $itemsIds, $batchSize = 100)
    {
        $indexer = $this->getIndexer($index);

        do {
            $itemsIdsToProcess = array_splice($itemsIds, 0, $batchSize);
            $items = $indexer->getItemsByIds($itemsIdsToProcess);
            $this->processItems($index, $indexer, $items);
        } while ($itemsIdsToProcess);
    }

    public function removeItems($index, $itemsIds)
    {
        return $this->conn->createQueryBuilder()
            ->delete($this->conn->getEscaper()->quoteIdentifier($index))
            ->where('id IN :ids')
            ->setParameter('ids', $itemsIds)
            ->execute();
    }

    public function getIndexRange($index)
    {
        return $this->conn
            ->createQueryBuilder()
            ->select('MIN(id) AS `min`, MAX(id) AS `max`')
            ->from($this->conn->getEscaper()->quoteIdentifier($index))
            ->getResult()
            ->getSingleRow();
    }

    public function truncate($index)
    {
        $this->conn->executeUpdate(sprintf('TRUNCATE RTINDEX %s', $this->conn->getEscaper()->quoteIdentifier($index)));
    }

    /**
     * @param $index
     *
     * @return IndexerInterface
     *
     * @throws \InvalidArgumentException When index not defined
     */
    protected function getIndexer($index)
    {
        if (!isset($this->indexers[$index])) {
            throw new \InvalidArgumentException('Unknown index');
        }

        return $this->container->get($this->indexers[$index]);
    }

    /**
     * @param $index
     * @param IndexerInterface $indexer
     * @param $items
     */
    protected function processItems($index, IndexerInterface $indexer, $items)
    {
        $items = $indexer->processItems($items);
        if (!count($items)) {
            return;
        }

        $insertQb = $this->conn
            ->createQueryBuilder()
            ->replace($this->conn->getEscaper()->quoteIdentifier($index));

        foreach ($items as $item) {
            $insertQb->addValues(
                $this->conn->getEscaper()->quoteSetArr(
                    $indexer->serializeItem($item)
                )
            );
        }

        $insertQb->execute();
    }
}
