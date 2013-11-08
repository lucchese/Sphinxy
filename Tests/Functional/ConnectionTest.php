<?php

namespace Brouzie\Sphinxy\Tests\Functional;

use Brouzie\Sphinxy\Connection;

class ConnectionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    protected static $conn;

    protected $index = 'sphinxy_products';

    public static function setUpBeforeClass()
    {
        $dsn = 'mysql:host=192.168.56.101;port=9306';
        self::$conn = new Connection(new \PDO($dsn));
        self::$conn->executeQuery('TRUNCATE RTINDEX sphinxy_products');
    }

    public function testInsert()
    {
        $this->assertEquals(2, self::$conn->executeUpdate("INSERT INTO $this->index (id, title) VALUES(1, 'title1'), (2, 'title2')"));
        $this->assertEquals(2, self::$conn->executeUpdate("INSERT INTO $this->index (id, title) VALUES(3, 'title3'), (4, 'title4')"));
        $this->assertEquals(1, self::$conn->executeUpdate("INSERT INTO $this->index (id, title) VALUES(5, 'title5')"));

        $resultSet = self::$conn->executeQuery("SELECT * FROM $this->index LIMIT 2");
        $this->assertCount(2, $resultSet->getIterator());
        $this->assertEquals(5, $resultSet->getTotalCount());
    }

    public function testReplace()
    {
        $this->assertEquals(1, self::$conn->executeUpdate("REPLACE INTO $this->index (id, title, category_id) VALUES(3, 'title3-new', 33)"));

        $resultSet = self::$conn->executeQuery("SELECT id, category_id FROM $this->index WHERE id = 3");

        $this->assertEquals(array('id' => '3', 'category_id' => '33'), current($resultSet->getIterator()));
    }
}
