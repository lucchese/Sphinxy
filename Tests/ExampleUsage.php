<?php

namespace Brouzie\Sphinxy\Tests;

use Brouzie\Sphinxy\Connection;

class ExampleUsage
{
    protected function getConnection()
    {
        $pdo = new \PDO('localhost');

        return new Connection($pdo);
    }

    public function exampleRun()
    {
        $conn = $this->getConnection();

        $products =$conn->createQueryBuilder()
            ->select('*')
            ->from('products')
            ->where('city_id = :city_id')
            ->setParameter('city_id', 1)
            ->andWhere('categories_id IN (:cat_ids)')
            ->setParameter('cat_ids', array(2, 3))
            ->getResult()
        ;

        $qb = $conn->createQueryBuilder()
            ->insert('products');

        $data = array(
            array('id' => 1, 'title' => 'a1'),
            array('id' => 2, 'title' => 'a2'),
        );
        foreach ($data as $item) {
            $qb->addValues($item);
            $qb->addValues($conn->getEscaper()->quoteSetArr($item));
        }
        $qb->execute();
    }
}
