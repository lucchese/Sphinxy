<?php

namespace Brouzie\Sphinxy\Tests\Connection;

use Brouzie\Sphinxy\Connection\PdoConnection;

class PdoConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorIsLazy()
    {
        try {
            new PdoConnection('invalid dsn');
        } catch (\Exception $e) {
            $this->fail('Constructor shouldn\'t connect');
        }
    }

    /**
     * @expectedException Brouzie\Sphinxy\Exception\ConnectionException
     */
    public function testExceptionWhenCouldNotConnect()
    {
        $conn = new PdoConnection('invalid dsn');
        $conn->query('SELECT 1 FROM products');
    }

    public function testConnection()
    {
        $conn = new PdoConnection($_ENV['sphinx_dsn']);
        $result = $conn->query('SHOW TABLES');

        $this->assertContains(array('Index' => 'products', 'Type' => 'rt'), $result);
    }
}
