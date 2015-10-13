<?php

namespace Brouzie\Sphinxy;

use Brouzie\Sphinxy\Connection\ConnectionInterface;
use Brouzie\Sphinxy\Logging\LoggerInterface;
use Brouzie\Sphinxy\Query\MultiResultSet;
use Brouzie\Sphinxy\Query\ResultSet;

class Connection
{
    /**
     * @var ConnectionInterface
     */
    private $conn;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(ConnectionInterface $conn)
    {
        $this->conn = $conn;
    }

    /**
     * @param LoggerInterface|null $logger
     */
    public function setLogger(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger()
    {
        return $this->logger;
    }

    public function executeUpdate($query, array $params = array())
    {
        if (null !== $this->logger) {
            $this->logger->startQuery($query);
        }

        $result = $this->conn->exec($this->prepareQuery($query, $params));

        if (null !== $this->logger) {
            $this->logger->stopQuery();
        }

        return $result;
    }

    public function executeQuery($query, array $params = array())
    {
        if (null !== $this->logger) {
            $this->logger->startQuery($query, $params);
        }

        $result = $this->conn->query($this->prepareQuery($query, $params));

        if (null !== $this->logger) {
            $this->logger->stopQuery();
        }
        $meta = $this->conn->query('SHOW META');

        return new ResultSet($result, $meta);
    }

    public function executeMultiQuery($query, array $params = array())
    {
        if (null !== $this->logger) {
            $this->logger->startQuery($query, $params);
        }

        $results = $this->conn->multiQuery($this->prepareQuery($query, $params));

        if (null !== $this->logger) {
            $this->logger->stopQuery();
        }
        $meta = $this->conn->query('SHOW META');

        return new MultiResultSet($results, $meta);
    }

    public function quote($value)
    {
        return $this->conn->quote($value);
    }

    public function getEscaper()
    {
        if (null === $this->escaper) {
            $this->escaper = new Escaper($this->conn);
        }

        return $this->escaper;
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    protected function prepareQuery($query, $params)
    {
        return Util::prepareQuery($query, $params, $this->getEscaper());
    }
}
