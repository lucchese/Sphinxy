<?php

namespace Brouzie\Sphinxy\Tests;

use Brouzie\Sphinxy\Escaper;
use Brouzie\Sphinxy\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $conn;

    public function setUp()
    {
        $this->conn = $this->getMock('Brouzie\Sphinxy\Connection', array(), array(), '', false);

        $escaper = new Escaper($this->conn);

        $this->conn->expects($this->any())
            ->method('getEscaper')
            ->will($this->returnValue($escaper));
    }

    public function testInstanceCreation()
    {
        $qb = $this->getQueryBuilder();

        $this->assertInstanceOf('Brouzie\Sphinxy\QueryBuilder', $qb);
    }

    public function testSimpleSelect()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('users');

        $this->assertEquals('SELECT * FROM users', $qb->getSql());
    }

    public function testSimpleSelectWithExtraColumnsAndAliases()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('users')
            ->addSelect('city_id = 123 AS c')
        ;

        $this->assertEquals('SELECT *, city_id = 123 AS c FROM users', $qb->getSql());
    }

    public function testSimpleSelectFromMultipleSources()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('users')
            ->addFrom('newcommers')
        ;

        $this->assertEquals('SELECT * FROM users, newcommers', $qb->getSql());
    }

    public function testSimpleSelectWithLimit()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('users')
            ->setMaxResults(10)
        ;

        $this->assertEquals('SELECT * FROM users LIMIT 10', $qb->getSql());
    }

    //TODO: offset without limit?
    public function testSimpleSelectWithLimitAndOffset()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('users')
            ->setMaxResults(10)
            ->setFirstResult(100)
        ;

        $this->assertEquals('SELECT * FROM users LIMIT 10, 100', $qb->getSql());
    }

    public function testSimpleWhere()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->where('qty = :qty');

        $this->assertEquals('SELECT * FROM products WHERE qty = :qty', $qb->getSql());
    }

    public function testCompositeWhere()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->where('qty = :qty')
            ->andWhere('price > 10')
        ;

        $this->assertEquals('SELECT * FROM products WHERE qty = :qty AND price > 10', $qb->getSql());
    }

    public function testOrderBySelectExpression()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->addSelect('city = :city AS best_city')
            ->from('products')
            ->where('qty = :qty')
            ->orderBy('price', 'ASC')
            ->addOrderBy('best_city', 'DESC');

        $this->assertEquals('SELECT *, city = :city AS best_city FROM products WHERE qty = :qty ORDER BY price ASC, best_city DESC', $qb->getSql());
    }

    public function testGroupBy()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->groupBy('company_id');

        $this->assertEquals('SELECT * FROM products GROUP BY company_id', $qb->getSql());
    }

    public function testWhereWithMultipleGroupBy()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->where('qty = :qty')
            ->groupBy('city_id')
            ->addGroupBy('company_id');

        $this->assertEquals('SELECT * FROM products WHERE qty = :qty GROUP BY city_id, company_id', $qb->getSql());
    }


    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->conn);
    }
}
