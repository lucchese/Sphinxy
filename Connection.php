<?php

namespace Brouzie\Sphinxy;

use Brouzie\Sphinxy\Logging\LoggerInterface;

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

        $result = $this->pdo->query($this->prepareQuery($query, $params))->fetchAll(\PDO::FETCH_ASSOC);

        if (null !== $this->logger) {
            $this->logger->stopQuery();
        }

        return $result;
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
