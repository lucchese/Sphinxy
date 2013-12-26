<?php

namespace Brouzie\Sphinxy\Indexer;

interface IndexerInterface
{
    public function getRangeCriterias();

    public function getItemsByIds(array $ids);

    public function getItemsByInterval($idFrom, $idTo);

    public function processItems(array $items);

    public function serializeItem($item);
}
