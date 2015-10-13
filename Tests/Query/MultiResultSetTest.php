<?php

namespace Brouzie\Sphinxy\Tests\Query;

use Brouzie\Sphinxy\Connection;

class MultiResultSetTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Connection
     */
    private static $conn;

    public static function setUpBeforeClass()
    {
        self::$conn = new Connection(new Connection\PdoConnection($_ENV['sphinx_dsn']));
    }

    public function setUp()
    {
        self::$conn->executeUpdate('TRUNCATE RTINDEX products');
        self::$conn->executeUpdate("INSERT INTO products (id, title) VALUES (1, 'product 1'), (2, 'product 2'), (3, 'product 3'), (4, 'product 4'), (5, 'product 5'), (6, 'product 6'), (7, 'product 7')");
    }

    public function testMultiResultSet()
    {
        $multiResultSet = self::$conn->executeMultiQuery('SELECT id FROM products ORDER BY id ASC;SELECT id FROM products ORDER BY id DESC');

        $this->assertInstanceOf('Brouzie\Sphinxy\Query\MultiResultSet', $multiResultSet);
        $this->assertCount(2, $multiResultSet);

        $expected = array(
            array(
                array('id' => 1),
                array('id' => 2),
                array('id' => 3),
                array('id' => 4),
                array('id' => 5),
                array('id' => 6),
                array('id' => 7),
            ),
            array(
                array('id' => 7),
                array('id' => 6),
                array('id' => 5),
                array('id' => 4),
                array('id' => 3),
                array('id' => 2),
                array('id' => 1),
            ),
        );

        foreach ($multiResultSet as $i => $resultSet) {
            $this->assertInstanceOf('Brouzie\Sphinxy\Query\SimpleResultSet', $resultSet);
            $this->assertEquals($expected[$i], iterator_to_array($resultSet->getIterator()));
        }
    }
}
