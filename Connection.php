<?php

namespace Brouzie\Sphinxy;

use Brouzie\Sphinxy\Exception\ConnectionException;
use Brouzie\Sphinxy\Logging\LoggerInterface;
use Brouzie\Sphinxy\Query\ResultSet;

class Connection
{
    private $pdo;

    /**
     * @var Escaper
     */
    private $escaper;

    /**
     * @var LoggerInterface|null
     */
    private $logger;

    public function __construct(\PDO $pdo)
    {
        $this->pdo = $pdo;
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

        $result = $this->pdo->exec($this->prepareQuery($query, $params));
        if (false === $result) {
            list($code, , $message) = $this->pdo->errorInfo();

            throw new ConnectionException($message, $code);
        }

        if (null !== $this->logger) {
            $this->logger->stopQuery();
        }

        return $result;
    }

    public function executeQuery($query, array $params = array())
    {
        if (null !== $this->logger) {
            $this->logger->startQuery($query);
        }

        $stmt = $this->pdo->query($this->prepareQuery($query, $params));
        if (!is_object($stmt)) {
            list($code, , $message) = $this->pdo->errorInfo();

            throw new ConnectionException($message, $code);
        }
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        if (null !== $this->logger) {
            $this->logger->stopQuery();
        }
        //TODO: use multiquery?
        $meta = $this->pdo->query('SHOW META')->fetchAll(\PDO::FETCH_ASSOC);

        return new ResultSet($result, $meta);
    }

    public function quote($value)
    {
        if (($value = $this->pdo->quote((string)$value)) === false) {
            throw new \Exception($this->pdo->errorInfo(), $this->pdo->errorCode());
        }

        return $value;
    }

    public function getEscaper()
    {
        if (null === $this->escaper) {
            $this->escaper = new Escaper($this);
        }

        return $this->escaper;
    }

    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    protected function prepareQuery($query, $params)
    {
        return Util::prepareQuery($query, $params, $this->getEscaper());
    }
}
