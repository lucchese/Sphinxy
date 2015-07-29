<?php

namespace Brouzie\Sphinxy\Tests;

use Brouzie\Sphinxy\QueryBuilder;

class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    protected $conn;

    public function setUp()
    {
        $this->conn = $this->getMock('Brouzie\Sphinxy\Connection', array(), array(), '', false);
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

        $this->assertEquals('SELECT * FROM users LIMIT 0, 10', $qb->getSql());
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

        $this->assertEquals('SELECT * FROM users LIMIT 100, 10', $qb->getSql());
    }

    public function testSelectWithSubselect()
    {
        $innerQb = $this->getQueryBuilder();
        $innerQb->select('id, name, TESTUDF(id) AS udf_result')
            ->from('users')
            ->where('id > :id')
            ->setParameter('id', 5)
            ->orderBy('name')
        ;

        $qb = $this->getQueryBuilder();
        $qb->select('*')
            ->from($innerQb)
            ->orderBy('udf_result', 'DESC')
        ;

        $this->assertEquals('SELECT * FROM (SELECT id, name, TESTUDF(id) AS udf_result FROM users WHERE id > :id ORDER BY name ASC) ORDER BY udf_result DESC', $qb->getSql());
        $this->assertEquals(array('id' => 5), $qb->getParameters());
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

    public function testOrderByRandSelectExpression()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->addSelect('city = :city AS best_city')
            ->from('products')
            ->where('qty = :qty')
            ->orderBy('price')
            ->addOrderBy('best_city', 'DESC')
            ->addOrderBy('RAND()')
        ;

        $this->assertEquals('SELECT *, city = :city AS best_city FROM products WHERE qty = :qty ORDER BY price ASC, best_city DESC, RAND()', $qb->getSql());
    }

    public function testGroupBy()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->groupBy('company_id');

        $this->assertEquals('SELECT * FROM products GROUP BY company_id', $qb->getSql());
    }

    public function testGroupByWithLimit()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->groupBy('company_id', 3);

        $this->assertEquals('SELECT * FROM products GROUP 3 BY company_id', $qb->getSql());
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

    public function testSimpleInsert()
    {
        $qb = $this->getQueryBuilder();

        $qb->insert('products')
            ->values(array('id' => 1, 'title' => "'product 1'"))
        ;

        $this->assertEquals("INSERT INTO products (id, title) VALUES (1, 'product 1')", $qb->getSql());
    }

    public function testInsertMultipleValues()
    {
        $qb = $this->getQueryBuilder();

        $qb->insert('products')
            ->values(array('id' => 1, 'title' => "'product 1'"))
            ->addValues(array('id' => 2, 'title' => "'product 2'"))
        ;

        $this->assertEquals("INSERT INTO products (id, title) VALUES (1, 'product 1'), (2, 'product 2')", $qb->getSql());
    }

    public function testSimpleReplace()
    {
        $qb = $this->getQueryBuilder();

        $qb->replace('products')
            ->values(array('id' => 1, 'title' => "'product 1'"))
        ;

        $this->assertEquals("REPLACE INTO products (id, title) VALUES (1, 'product 1')", $qb->getSql());
    }

    public function testSimpleUpdate()
    {
        $qb = $this->getQueryBuilder();

        $qb->update('products')
            ->set('title', "'product 2'")
            ->where('id = 1')
        ;

        $this->assertEquals("UPDATE products SET title = 'product 2' WHERE id = 1", $qb->getSql());
    }

    public function testUpdateWithMultipleSet()
    {
        $qb = $this->getQueryBuilder();

        $qb->update('products')
            ->set('title', "'product 2'")
            ->set('attributes', "(1, 2, 3)")
            ->where('id = 1')
        ;

        $this->assertEquals("UPDATE products SET title = 'product 2', attributes = (1, 2, 3) WHERE id = 1", $qb->getSql());
    }

    public function testCreateParameter()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->where('qty = '.$qb->createParameter(10))
            ->andWhere('price > '.$qb->createParameter(20))
        ;

        $this->assertEquals('SELECT * FROM products WHERE qty = :gen_1 AND price > :gen_2', $qb->getSql());
        $this->assertEquals(array('gen_1' => 10, 'gen_2' => 20), $qb->getParameters());
    }

    public function testCreateParameterWithPrefix()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->where('qty = '.$qb->createParameter(10))
            ->andWhere('price > '.$qb->createParameter(20, 'price'))
            ->andWhere('attributes.10 = '.$qb->createParameter(101, 'attributes.10'))
        ;

        $this->assertEquals('SELECT * FROM products WHERE qty = :gen_1 AND price > :price2 AND attributes.10 = :attributes_103', $qb->getSql());
        $this->assertEquals(array('gen_1' => 10, 'price2' => 20, 'attributes_103' => 101), $qb->getParameters());
    }

    public function testQueryWithOption()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->where('qty = :qty')
            ->setOption('max_matches', 1000)
        ;

        $this->assertEquals('SELECT * FROM products WHERE qty = :qty OPTION max_matches = 1000', $qb->getSql());
    }

    public function testQueryWithMultipleOptions()
    {
        $qb = $this->getQueryBuilder();

        $qb->select('*')
            ->from('products')
            ->where('qty = :qty')
            ->setOption('max_matches', 1000)
            ->setOption('max_query_time', 10)
        ;

        $this->assertEquals('SELECT * FROM products WHERE qty = :qty OPTION max_matches = 1000, max_query_time = 10', $qb->getSql());
    }

    protected function getQueryBuilder()
    {
        return new QueryBuilder($this->conn);
    }
}
