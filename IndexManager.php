<?php

namespace Brouzie\Sphinxy;

use Brouzie\Sphinxy\Exception\ConnectionException;
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

    public function reindex($index, $batchSize = 1000, callable $batchCallback = null, array $rangeCriterias = array())
    {
        $logger = $this->conn->getLogger();
        $this->conn->setLogger(null);

        $indexer = $this->getIndexer($index);
        $range = array_replace($indexer->getRangeCriterias(), $rangeCriterias);

        $reindexCallback = function ($data) use ($index, $indexer, $batchCallback, $range) {
            if (null !== $batchCallback) {
                $batchCallback(
                    array(
                        'id_from' => $data['id_from'],
                        'id_to' => $data['id_to'],
                        'min_id' => $range['min'],
                        'max_id' => $range['max'],
                    )
                );
            }

            $items = $indexer->getItemsByInterval($data['id_from'], $data['id_to']);
            $this->processItems($index, $indexer, $items);
        };

        $idFrom = $range['min'];
        do {
            $idTo = $idFrom + $batchSize;
            $this->safeExecute($reindexCallback, array(array('id_from' => $idFrom, 'id_to' => $idTo)));
            $idFrom = $idTo;
        } while ($idFrom <= $range['max']);
        $this->conn->setLogger($logger);
    }

    public function reindexItems($index, $itemsIds, $batchSize = 100)
    {
        $indexer = $this->getIndexer($index);

        $reindexItemsCallback = function ($itemsIdsToProcess) use ($index, $indexer) {
            $items = $indexer->getItemsByIds($itemsIdsToProcess);
            $this->processItems($index, $indexer, $items);
        };

        do {
            $itemsIdsToProcess = array_splice($itemsIds, 0, $batchSize);
            $this->safeExecute($reindexItemsCallback);
        } while ($itemsIdsToProcess);
    }

    public function removeItems($index, $itemsIds)
    {
        $removeItemsCallback = function () use ($index, $itemsIds) {
            return $this->conn->createQueryBuilder()
                ->delete($this->conn->getEscaper()->quoteIdentifier($index))
                ->where('id IN :ids')
                ->setParameter('ids', $itemsIds)
                ->execute();
        };

        return $this->safeExecute($removeItemsCallback);
    }

    public function getIndexRange($index)
    {
        $getIndexRangeCallback = function () use ($index) {
            return $this->conn
                ->createQueryBuilder()
                ->select('MIN(id) AS `min`, MAX(id) AS `max`')
                ->from($this->conn->getEscaper()->quoteIdentifier($index))
                ->getResult()
                ->getSingleRow(array('min' => 0, 'max' => 0));
        };

        return $this->safeExecute($getIndexRangeCallback);
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

        $escaper = $this->conn->getEscaper();
        $insertQb = $this->conn
            ->createQueryBuilder()
            ->replace($escaper->quoteIdentifier($index));

        foreach ($items as $item) {
            $insertQb->addValues($escaper->quoteSetArr($indexer->serializeItem($item)));
        }

        $insertQb->execute();
    }

    protected function safeExecute(callable $callable, array $args = array(), $retriesCount = 3, $sleep = 20)
    {
        for ($i = 0; $i < $retriesCount; $i++) {
            try {
                return call_user_func_array($callable, $args);
            } catch (ConnectionException $e) {
                sleep($sleep);
                $this->conn->checkConnection();
                continue;
            }
        }

        throw $e;
    }
}
