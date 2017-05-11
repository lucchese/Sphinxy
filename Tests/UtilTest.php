<?php

namespace Brouzie\Sphinxy\Tests;

use Brouzie\Sphinxy\Util;

class UtilTest extends \PHPUnit_Framework_TestCase
{
    protected $escaper;

    protected function setUp()
    {
        $this->escaper = $this->getMock('Brouzie\Sphinxy\Escaper', array(), array(), '', false);
        $this->escaper->expects($this->any())
            ->method('quote')
            ->will($this->returnCallback(function ($item) {
                return '-'.$item.'-';
            }));
    }

    public function prepareQueryDataProvider()
    {
        return array(
            array('', '', array()),
            array('SELECT -1-', 'SELECT :foo', array('foo' => 1)),
            array('SELECT -1-, -2-', 'SELECT :foo, :bar', array('foo' => 1, 'bar' => 2)),
            array('SELECT ":foo" FROM Foo WHERE bar IN (-1-, -2-) AND "a" = a', 'SELECT ":foo" FROM Foo WHERE bar IN (:name1, :name2) AND "a" = a', array('name1' => 1, 'name2' => 2)),
        );
    }

    /**
     * @dataProvider prepareQueryDataProvider
     */
    public function testPrepareQuery($expected, $sql, $params)
    {
        $this->assertEquals($expected, Util::prepareQuery($sql, $params, $this->escaper));
    }

    public function parameterNotFoundDataProvider()
    {
        return array(
            array('SELECT :foo FROM bar', array()),
            array('SELECT :foo FROM bar', array(':foo' => 1)),
            array('SELECT :foo FROM bar WHERE ololo = :ololo', array('foo' => 1)),
        );
    }

    /**
     * @dataProvider parameterNotFoundDataProvider
     * @expectedException \Brouzie\Sphinxy\Exception\ParameterNotFoundException
     */
    public function testParameterNotFound($sql, $params)
    {
        Util::prepareQuery($sql, $params, $this->escaper);
    }

    public function extraParametersDataProvider()
    {
        return array(
            array('SELECT :foo FROM bar', array('foo' => 1, 'bar' => 2), 'Extra parameters found: bar.'),
            array('SELECT :foo FROM bar', array('foo' => 1, ':foo' => 2), 'Extra parameters found: :foo.'),
            array('SELECT * FROM bar WHERE ololo = 1', array('foo' => 1), 'Extra parameters found: foo.'),
        );
    }

    /**
     * @dataProvider extraParametersDataProvider
     */
    public function testExtraParametersAdded($sql, $params, $exceptionMessage)
    {
        $this->setExpectedException('Brouzie\Sphinxy\Exception\ExtraParametersException', $exceptionMessage);

        Util::prepareQuery($sql, $params, $this->escaper);
    }
}
