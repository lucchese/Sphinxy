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
        self::$conn->executeUpdate("INSERT INTO $this->index (id, title) VALUES(1, 'title1'), (2, 'title2')");
    }
}
