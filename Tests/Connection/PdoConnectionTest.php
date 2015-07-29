<?php

namespace Brouzie\Sphinxy\Tests\Connection;

use Brouzie\Sphinxy\Connection\PdoConnection;

class PdoConnectionTest extends \PHPUnit_Framework_TestCase
{
    public function testConstructorIsLazy()
    {
        try {
            new PdoConnection('wrong dsn');
        } catch (\Exception $e) {
            $this->fail('Constructor shouldn\'t connect');
        }
    }

    /**
     * @expectedException Brouzie\Sphinxy\Exception\ConnectionException
     */
    public function testExceptionWhenCouldNotConnect()
    {
        $conn = new PdoConnection('wrong dsn');
        $conn->query('SELECT 1 FROM products');
    }
}
